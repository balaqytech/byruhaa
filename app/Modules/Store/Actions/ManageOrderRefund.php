<?php

namespace App\Modules\Store\Actions;

use App\Enums\LedgerAccountType;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Modules\Finance\Actions\PostFinancialEvent;
use App\Modules\Finance\Contracts\PaymentService;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Models\FinancialEvent;
use App\Modules\Finance\Models\LedgerAccount;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderItem;
use App\Modules\Store\Models\OrderRefundLine;
use App\Modules\Store\Models\OrderRefundRequest;
use App\Modules\Store\States\Order\Refunded;
use App\Modules\Store\States\Order\RefundPending;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ManageOrderRefund
{
    public function __construct(
        private WalletService $wallets,
        private PaymentService $payments,
        private PostFinancialEvent $postFinancialEvent,
        private ChangeOrderState $changeOrderState,
    ) {}

    /** @param array<int, int> $itemQuantities */
    public function request(Order $order, ?int $customerId, ?int $actorUserId, int $amountBaisa, string $reason, string $operationKey, array $itemQuantities = [], bool $windowOverride = false): OrderRefundRequest
    {
        if ($actorUserId !== null) {
            $this->assertStaff($actorUserId, $order);
        } elseif ($customerId === null && $operationKey !== 'store-expired:'.$order->id) {
            throw ValidationException::withMessages(['refund' => 'A customer or authorized employee is required.']);
        }
        if (blank($reason) || ($windowOverride && ($actorUserId === null || ! User::query()->findOrFail($actorUserId)->isPanelAdministrator()))) {
            throw ValidationException::withMessages(['refund' => 'A reason and administrator authorization are required for a refund window exception.']);
        }

        return DB::transaction(function () use ($order, $customerId, $actorUserId, $amountBaisa, $reason, $operationKey, $itemQuantities, $windowOverride): OrderRefundRequest {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->paid_at === null || ! in_array($order->payment_method, ['cash', 'wallet', 'thawani'], true)) {
                throw ValidationException::withMessages(['refund' => 'Only paid Store orders can be refunded.']);
            }
            if ($customerId !== null && (int) $order->customer_id !== $customerId) {
                throw ValidationException::withMessages(['refund' => 'This order does not belong to the customer.']);
            }
            if (! $windowOverride) {
                $completedAt = $order->statusHistory()->where('to_status', OrderStatus::Completed->value)->value('created_at');
                if ($completedAt !== null && now()->greaterThan(Carbon::parse($completedAt)->addHours(24))) {
                    throw ValidationException::withMessages(['refund' => 'The 24-hour refund request window has expired.']);
                }
            }
            if ($windowOverride && $actorUserId === null) {
                throw ValidationException::withMessages(['refund' => 'Only an administrator can override the refund window.']);
            }

            $existing = OrderRefundRequest::query()->where('operation_key', $operationKey)->first();
            if ($existing instanceof OrderRefundRequest) {
                $existingQuantities = $existing->lines()->whereNotNull('order_item_id')->pluck('quantity', 'order_item_id')->mapWithKeys(fn ($quantity, $itemId): array => [(int) $itemId => (int) $quantity])->all();
                ksort($existingQuantities);
                ksort($itemQuantities);
                if ($existing->order_id !== $order->id || ($itemQuantities === [] ? $existing->amount_baisa !== $amountBaisa : $existingQuantities !== $itemQuantities)) {
                    throw ValidationException::withMessages(['refund' => 'Refund operation key was reused with different details.']);
                }

                return $existing;
            }

            $reservedRefunds = $order->refundRequests()->whereIn('status', ['pending', 'approved', 'processing', 'manual_review', 'failed', 'paid'])->get();
            $remaining = $order->total_baisa - $reservedRefunds->sum('amount_baisa');
            if ($remaining < 1) {
                throw ValidationException::withMessages(['refund' => 'No refundable order amount remains.']);
            }

            $lines = [];
            if ($itemQuantities !== []) {
                $amountBaisa = 0;
                foreach ($itemQuantities as $itemId => $quantity) {
                    $item = OrderItem::query()->where('order_id', $order->id)->whereKey($itemId)->lockForUpdate()->firstOrFail();
                    $usedLines = OrderRefundLine::query()->where('order_item_id', $item->id)
                        ->whereHas('refund', fn ($query) => $query->whereIn('status', ['pending', 'approved', 'processing', 'manual_review', 'failed', 'paid']))->get();
                    $usedQuantity = (int) $usedLines->sum('quantity');
                    if ($quantity < 1 || $quantity > $item->quantity - $usedQuantity) {
                        throw ValidationException::withMessages(['items' => 'Refund item quantity exceeds the available quantity.']);
                    }
                    $lastUnits = $quantity === $item->quantity - $usedQuantity;
                    $lineAmount = $lastUnits ? $item->line_total_baisa - $usedLines->sum('amount_baisa') : intdiv($item->line_total_baisa * $quantity, $item->quantity);
                    $lineSubtotal = $lastUnits ? $item->line_subtotal_baisa - $usedLines->sum('subtotal_baisa') : intdiv($item->line_subtotal_baisa * $quantity, $item->quantity);
                    $lines[] = ['order_item_id' => $item->id, 'quantity' => $quantity, 'amount_baisa' => $lineAmount, 'subtotal_baisa' => $lineSubtotal, 'vat_baisa' => $lineAmount - $lineSubtotal];
                    $amountBaisa += $lineAmount;
                }
            } else {
                if ($amountBaisa < 1 || $amountBaisa > $remaining) {
                    throw ValidationException::withMessages(['amount' => 'Refund amount exceeds the remaining order amount.']);
                }
                $usedVat = (int) $reservedRefunds->sum('vat_baisa');
                $vat = $amountBaisa === $remaining
                    ? $order->vat_baisa - $usedVat
                    : min($order->vat_baisa - $usedVat, intdiv($amountBaisa * $order->vat_baisa, $order->total_baisa));
                $lines[] = ['order_item_id' => null, 'quantity' => null, 'amount_baisa' => $amountBaisa, 'subtotal_baisa' => $amountBaisa - $vat, 'vat_baisa' => $vat];
            }
            if ($amountBaisa < 1 || $amountBaisa > $remaining) {
                throw ValidationException::withMessages(['amount' => 'Refund amount exceeds the remaining order amount.']);
            }
            $subtotalBaisa = array_sum(array_column($lines, 'subtotal_baisa'));
            $vatBaisa = array_sum(array_column($lines, 'vat_baisa'));
            if ($subtotalBaisa + $vatBaisa !== $amountBaisa
                || $subtotalBaisa > $order->subtotal_baisa - $reservedRefunds->sum('subtotal_baisa')
                || $vatBaisa > $order->vat_baisa - $reservedRefunds->sum('vat_baisa')) {
                throw ValidationException::withMessages(['refund' => 'Refund net and VAT exceed the remaining invoice amounts.']);
            }

            $refund = $order->refundRequests()->create([
                'customer_id' => $customerId,
                'requested_by_user_id' => $actorUserId,
                'operation_key' => $operationKey,
                'reason' => $reason,
                'amount_baisa' => $amountBaisa,
                'subtotal_baisa' => $subtotalBaisa,
                'vat_baisa' => $vatBaisa,
                'currency' => $order->currency,
                'status' => 'pending',
                'admin_window_override' => $windowOverride,
            ]);
            $refund->lines()->createMany($lines);

            return $refund;
        });
    }

    public function approve(OrderRefundRequest $refund, int $actorUserId, ?string $note = null): OrderRefundRequest
    {
        $this->assertStaff($actorUserId, $refund->order);
        $refund = DB::transaction(function () use ($refund, $actorUserId, $note): OrderRefundRequest {
            $order = Order::query()->whereKey($refund->order_id)->lockForUpdate()->firstOrFail();
            $refund = OrderRefundRequest::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($refund->status !== 'pending') {
                throw ValidationException::withMessages(['refund' => 'Only pending refunds can be approved.']);
            }
            $refund->forceFill(['status' => 'approved', 'approved_by_user_id' => $actorUserId, 'approved_at' => now(), 'review_note' => $note])->save();
            if ($order->status->getValue() !== OrderStatus::RefundPending->value && $order->status->getValue() !== OrderStatus::Refunded->value && $refund->amount_baisa + $order->refundRequests()->where('status', 'paid')->where('id', '!=', $refund->id)->sum('amount_baisa') >= $order->total_baisa) {
                $this->changeOrderState->execute($order, RefundPending::class, $actorUserId, 'Full order refund approved.');
            }

            return $refund;
        });

        return match ($refund->order->payment_method) {
            'wallet' => $this->completeWallet($refund),
            'thawani' => $this->processThawani($refund),
            default => $refund,
        };
    }

    public function reject(OrderRefundRequest $refund, int $actorUserId, string $reason): OrderRefundRequest
    {
        $this->assertStaff($actorUserId);

        return DB::transaction(function () use ($refund, $actorUserId, $reason): OrderRefundRequest {
            $refund = OrderRefundRequest::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($refund->status !== 'pending') {
                throw ValidationException::withMessages(['refund' => 'Only pending refunds can be rejected.']);
            }
            $refund->forceFill(['status' => 'rejected', 'approved_by_user_id' => $actorUserId, 'review_note' => $reason])->save();

            return $refund;
        });
    }

    public function autoRefundExpired(Order $order): OrderRefundRequest
    {
        if ($order->status->getValue() !== OrderStatus::RefundPending->value) {
            throw ValidationException::withMessages(['refund' => 'Only expired paid orders can use automatic compensation.']);
        }

        $refund = $this->request($order, null, null, $order->total_baisa, 'Store order expired before payment confirmation.', 'store-expired:'.$order->id);
        if (in_array($refund->status, ['paid', 'manual_review', 'failed'], true)) {
            return $refund;
        }
        if ($refund->status === 'pending') {
            $refund->forceFill(['status' => 'approved', 'approved_at' => now(), 'review_note' => 'Automatic compensation for expired paid order.'])->save();
        }

        return $this->processThawani($refund);
    }

    public function completeCash(OrderRefundRequest $refund, int $actorUserId, string $receiptReference): OrderRefundRequest
    {
        $this->assertStaff($actorUserId, $refund->order);
        if ($refund->order->payment_method !== 'cash' || blank($receiptReference)) {
            throw ValidationException::withMessages(['refund' => 'Cash refund requires a receipt reference.']);
        }

        return $this->complete($refund, $actorUserId, $receiptReference, null);
    }

    public function confirmManualThawani(OrderRefundRequest $refund, int $actorUserId, string $providerRefundReference, string $evidencePath): OrderRefundRequest
    {
        $this->assertStaff($actorUserId);
        if ($refund->order->payment_method !== 'thawani' || $refund->status !== 'manual_review' || blank($providerRefundReference) || blank($evidencePath)) {
            throw ValidationException::withMessages(['refund' => 'Provider refund evidence is required for manual confirmation.']);
        }

        return DB::transaction(function () use ($refund, $actorUserId, $providerRefundReference, $evidencePath): OrderRefundRequest {
            $paymentRefund = $this->paymentAttempts($refund)->where('state', PaymentRefundState::ManualRequired->value)->latest('id')->lockForUpdate()->firstOrFail();
            if ($paymentRefund->state !== PaymentRefundState::ManualRequired) {
                throw ValidationException::withMessages(['refund' => 'The provider refund is not awaiting manual confirmation.']);
            }
            if (PaymentRefund::query()->where('provider_refund_id', $providerRefundReference)->whereKeyNot($paymentRefund->id)->exists()) {
                throw ValidationException::withMessages(['refund' => 'Provider refund reference was already used.']);
            }
            $paymentRefund->forceFill([
                'state' => PaymentRefundState::Succeeded,
                'provider_refund_id' => $providerRefundReference,
                'provider_status' => 'manually_confirmed',
                'resolution_method' => 'provider_verified',
                'manual_reference' => $providerRefundReference,
                'manual_evidence_path' => $evidencePath,
                'manually_completed_at' => now(),
                'manually_completed_by_user_id' => $actorUserId,
                'processed_at' => now(),
            ])->save();
            $payment = $paymentRefund->payment()->lockForUpdate()->firstOrFail();
            $paidRefunds = (int) $payment->refunds()->where('state', PaymentRefundState::Succeeded->value)->sum('amount_baisa');
            $payment->forceFill(['state' => $paidRefunds >= $payment->amount_baisa ? PaymentState::Refunded : PaymentState::PartiallyRefunded])->save();
            $refund->forceFill(['evidence_path' => $evidencePath])->save();

            return $this->complete($refund, $actorUserId, $providerRefundReference, $paymentRefund);
        });
    }

    public function retryFailedThawani(OrderRefundRequest $refund, int $actorUserId): OrderRefundRequest
    {
        $this->assertStaff($actorUserId);
        $refund = DB::transaction(function () use ($refund): OrderRefundRequest {
            $refund = OrderRefundRequest::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($refund->status !== 'failed' || $refund->order->payment_method !== 'thawani') {
                throw ValidationException::withMessages(['refund' => 'Only a definitively failed Thawani refund can be retried.']);
            }
            $lastAttempt = $this->paymentAttempts($refund)->latest('id')->first();
            if (! $lastAttempt instanceof PaymentRefund || $lastAttempt->state !== PaymentRefundState::Failed) {
                throw ValidationException::withMessages(['refund' => 'Provider result must be verified before retry.']);
            }
            $refund->forceFill(['status' => 'approved'])->save();

            return $refund;
        });

        return $this->processThawani($refund, true);
    }

    private function completeWallet(OrderRefundRequest $refund): OrderRefundRequest
    {
        return DB::transaction(function () use ($refund): OrderRefundRequest {
            $this->wallets->refundPurchase($refund->order->reference, $refund->reference, $refund->amount_baisa);

            return $this->complete($refund, $refund->approved_by_user_id, 'WALLET-'.$refund->reference, null);
        });
    }

    private function processThawani(OrderRefundRequest $refund, bool $retry = false): OrderRefundRequest
    {
        if ($refund->status === 'paid' || $refund->status === 'manual_review') {
            return $refund;
        }
        if ($refund->status !== 'approved') {
            throw ValidationException::withMessages(['refund' => 'The order refund is not approved.']);
        }
        $refund->forceFill(['status' => 'processing'])->save();
        $operationKey = 'store-refund:'.$refund->id.($retry ? ':attempt-'.($this->paymentAttempts($refund)->count() + 1) : '');
        try {
            $result = $this->payments->refundPayment($refund->order->payment_reference, $refund->amount_baisa, $refund->reason, $operationKey);
        } catch (Throwable $exception) {
            $attempt = PaymentRefund::query()->where('operation_key', $operationKey)->first();
            $refund->forceFill(['status' => $attempt?->state === PaymentRefundState::Failed ? 'failed' : 'manual_review', 'review_note' => $exception->getMessage()])->save();

            return $refund;
        }

        if ($result->status !== 'succeeded') {
            $refund->forceFill(['status' => 'manual_review'])->save();

            return $refund;
        }
        $paymentRefund = PaymentRefund::query()->where('reference', $result->refundReference)->firstOrFail();

        return $this->complete($refund, $refund->approved_by_user_id, $result->refundReference, $paymentRefund);
    }

    private function complete(OrderRefundRequest $refund, ?int $actorUserId, string $payoutReference, ?PaymentRefund $paymentRefund): OrderRefundRequest
    {
        return DB::transaction(function () use ($refund, $actorUserId, $payoutReference, $paymentRefund): OrderRefundRequest {
            $order = Order::query()->whereKey($refund->order_id)->lockForUpdate()->firstOrFail();
            $refund = OrderRefundRequest::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($refund->status === 'paid' && $refund->payout_reference === $payoutReference) {
                return $refund;
            }
            if (! in_array($refund->status, ['approved', 'processing', 'manual_review'], true)) {
                throw ValidationException::withMessages(['refund' => 'The refund is not approved for payout.']);
            }
            $completed = FinancialEvent::query()->where('event_key', 'store-completion:'.$order->id)->exists();
            $debitCode = $completed ? LedgerAccount::STORE_SALES_RETURNS_CODE : LedgerAccount::STORE_DEFERRED_CODE;
            $debitType = $completed ? LedgerAccountType::Income : LedgerAccountType::Liability;
            [$creditCode, $creditType] = match ($order->payment_method) {
                'cash' => [LedgerAccount::CASH_ON_HAND_CODE, LedgerAccountType::Asset],
                'wallet' => [LedgerAccount::WALLET_LIABILITY_CODE, LedgerAccountType::Liability],
                'thawani' => [LedgerAccount::THAWANI_CLEARING_CODE, LedgerAccountType::Asset],
            };
            $lines = [];
            if ($refund->subtotal_baisa > 0) {
                $lines[] = ['code' => $debitCode, 'name' => $completed ? 'Store sales returns' : 'Store deferred revenue', 'type' => $debitType, 'debit' => $refund->subtotal_baisa, 'credit' => 0];
            }
            if ($refund->vat_baisa > 0) {
                $lines[] = ['code' => LedgerAccount::VAT_PAYABLE_CODE, 'name' => 'VAT payable', 'type' => LedgerAccountType::Liability, 'debit' => $refund->vat_baisa, 'credit' => 0];
            }
            $lines[] = ['code' => $creditCode, 'name' => $order->payment_method.' refund', 'type' => $creditType, 'debit' => 0, 'credit' => $refund->amount_baisa];
            $refund->forceFill(['status' => 'paid', 'paid_by_user_id' => $actorUserId, 'paid_at' => now(), 'payout_reference' => $payoutReference, 'payment_refund_id' => $paymentRefund?->id])->save();
            $this->postFinancialEvent->execute('store-refund:'.$refund->id, 'store_refund', 'store_refund', $refund->id, 'Store refund '.$refund->reference, $refund->currency, $refund->paid_at, $lines);

            $paid = (int) $order->refundRequests()->where('status', 'paid')->sum('amount_baisa');
            if ($paid >= $order->total_baisa && $order->status->getValue() === OrderStatus::RefundPending->value) {
                $this->changeOrderState->execute($order, Refunded::class, $actorUserId, 'Full refund payout confirmed.');
            }

            return $refund;
        });
    }

    private function assertStaff(int $actorUserId, ?Order $order = null): void
    {
        $actor = User::query()->findOrFail($actorUserId);
        if ($actor->isPanelAdministrator() || $actor->can('Update:OrderRefundRequest') || $actor->can('Update:Order')) {
            return;
        }

        if ($order !== null && $actor->can('Sell:Pos') && $actor->can('RefundCash:Pos')) {
            $storedOrder = Order::query()->findOrFail($order->id);
            if ($storedOrder->pos_cashier_user_id !== null && $storedOrder->payment_method === 'cash') {
                return;
            }
        }

        throw ValidationException::withMessages(['refund' => 'This employee cannot manage Store refunds.']);
    }

    private function paymentAttempts(OrderRefundRequest $refund): Builder
    {
        $base = 'store-refund:'.$refund->id;

        return PaymentRefund::query()->where(fn ($query) => $query->where('operation_key', $base)->orWhere('operation_key', 'like', $base.':attempt-%'));
    }
}
