<?php

namespace App\Modules\Finance\Services;

use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Modules\Finance\Actions\PostPaymentLedgerTransaction;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Data\WalletSpendData;
use App\Modules\Finance\Data\WalletSummaryData;
use App\Modules\Finance\Enums\WalletMovementType;
use App\Modules\Finance\Enums\WalletSettlementStatus;
use App\Modules\Finance\Enums\WalletTopUpStatus;
use App\Modules\Finance\Events\WalletMovementPosted;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Finance\Models\Wallet;
use App\Modules\Finance\Models\WalletMovement;
use App\Modules\Finance\Models\WalletPurchaseAllocation;
use App\Modules\Finance\Models\WalletSettlement;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DatabaseWalletService implements WalletService
{
    public function __construct(private PostPaymentLedgerTransaction $postPaymentLedgerTransaction) {}

    public function walletForMinorProfile(int $minorProfileId, string $currency = 'OMR'): Wallet
    {
        if (! config('byruhaa.wallets.enabled', false)) {
            throw ValidationException::withMessages(['wallet' => 'Wallets are currently disabled.']);
        }

        return Wallet::query()->firstOrCreate(
            ['minor_profile_id' => $minorProfileId],
            ['currency' => $currency, 'balance_baisa' => 0, 'status' => 'active'],
        );
    }

    public function summary(int $minorProfileId): WalletSummaryData
    {
        $wallet = $this->walletForMinorProfile($minorProfileId);

        return new WalletSummaryData(
            $wallet->id,
            $wallet->minor_profile_id,
            $wallet->balance_baisa,
            $wallet->currency,
            $wallet->status,
        );
    }

    public function creditTopUp(Payment $payment, bool $retryNotification = false): void
    {
        if ($payment->subject_type !== 'wallet_topup' || $payment->subject_reference === null || $payment->state !== PaymentState::Paid) {
            return;
        }

        $postedMovement = null;

        DB::transaction(function () use ($payment, $retryNotification, &$postedMovement): void {
            $payment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            $topUp = WalletTopUp::query()->where('reference', $payment->subject_reference)->lockForUpdate()->firstOrFail();
            $wallet = Wallet::query()->whereKey($topUp->wallet_id)->lockForUpdate()->firstOrFail();

            if (in_array($topUp->status, [
                WalletTopUpStatus::Credited->value,
                WalletTopUpStatus::Refunding->value,
                WalletTopUpStatus::Refunded->value,
            ], true)) {
                if ($retryNotification) {
                    $postedMovement = WalletMovement::query()
                        ->where('operation_key', 'wallet-top-up:'.$topUp->id)
                        ->lockForUpdate()
                        ->first();
                }

                return;
            }

            if ($payment->amount_baisa !== $topUp->amount_baisa || $payment->currency !== $topUp->currency) {
                throw ValidationException::withMessages(['payment' => 'The wallet top-up amount or currency does not match.']);
            }

            $operationKey = 'wallet-top-up:'.$topUp->id;
            $movement = WalletMovement::query()->where('operation_key', $operationKey)->lockForUpdate()->first();
            $balance = $wallet->balance_baisa;

            if (! $movement instanceof WalletMovement) {
                $balance += $topUp->amount_baisa;
                $movement = $wallet->movements()->create([
                    'wallet_top_up_id' => $topUp->id,
                    'operation_key' => $operationKey,
                    'type' => WalletMovementType::TopUp,
                    'credit_baisa' => $topUp->amount_baisa,
                    'debit_baisa' => 0,
                    'balance_after_baisa' => $balance,
                    'metadata' => ['payment_reference' => $payment->reference],
                ]);
                $postedMovement = $movement;
            }

            $wallet->forceFill(['balance_baisa' => $balance])->save();
            $topUp->forceFill([
                'status' => WalletTopUpStatus::Credited,
                'refundable_baisa' => $topUp->amount_baisa,
                'spendable_baisa' => $topUp->amount_baisa,
                'credited_at' => $payment->paid_at ?? now(),
                'refund_deadline_at' => ($payment->paid_at ?? now())->copy()->addHours((int) config('byruhaa.wallets.top_up_refund_window_hours', 24)),
            ])->save();
        });

        $this->postPaymentLedgerTransaction->execute($payment);

        if ($postedMovement instanceof WalletMovement) {
            WalletMovementPosted::dispatch($postedMovement->refresh());
        }
    }

    public function spend(int $minorProfileId, string $orderReference, int $amountBaisa, string $currency): WalletSpendData
    {
        if (! config('byruhaa.wallets.enabled', false)) {
            throw ValidationException::withMessages(['wallet' => 'Wallets are currently disabled.']);
        }

        if ($amountBaisa < 1) {
            throw ValidationException::withMessages(['payment' => 'The wallet payment amount is invalid.']);
        }

        $spend = DB::transaction(function () use ($minorProfileId, $orderReference, $amountBaisa, $currency): WalletSpendData {
            $wallet = Wallet::query()->where('minor_profile_id', $minorProfileId)->lockForUpdate()->first();

            if (! $wallet instanceof Wallet || $wallet->status !== 'active') {
                throw ValidationException::withMessages(['wallet' => 'The wallet is not available.']);
            }

            if ($wallet->currency !== $currency) {
                throw ValidationException::withMessages(['payment' => 'The wallet currency does not match the order.']);
            }

            $operationKey = 'wallet-purchase:'.$orderReference;
            $existing = WalletMovement::query()->where('operation_key', $operationKey)->first();

            if ($existing instanceof WalletMovement) {
                return new WalletSpendData($wallet->id, $existing->id, $existing->debit_baisa, $existing->balance_after_baisa, $currency);
            }

            if ($wallet->balance_baisa < $amountBaisa) {
                throw ValidationException::withMessages(['payment' => 'The wallet balance is insufficient.']);
            }

            $remaining = $amountBaisa;
            $topUps = WalletTopUp::query()
                ->where('wallet_id', $wallet->id)
                ->whereIn('status', [WalletTopUpStatus::Credited->value, WalletTopUpStatus::Refunding->value])
                ->whereColumn('spendable_baisa', '>', 'reserved_refund_baisa')
                ->orderBy('credited_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($topUps as $topUp) {
                if ($remaining === 0) {
                    break;
                }

                $allocationAmount = min($remaining, $topUp->spendable_baisa - $topUp->reserved_refund_baisa);
                $refundableAmount = min($allocationAmount, $topUp->refundable_baisa);
                $topUp->forceFill([
                    'spendable_baisa' => $topUp->spendable_baisa - $allocationAmount,
                    'refundable_baisa' => $topUp->refundable_baisa - $refundableAmount,
                ])->save();
                $topUp->purchaseAllocations()->create([
                    'wallet_id' => $wallet->id,
                    'order_reference' => $orderReference,
                    'amount_baisa' => $allocationAmount,
                    'refund_deadline_at' => $topUp->refund_deadline_at,
                ]);
                $remaining -= $allocationAmount;
            }

            if ($remaining > 0) {
                throw ValidationException::withMessages(['payment' => 'The wallet funds could not be allocated safely.']);
            }

            $balance = $wallet->balance_baisa - $amountBaisa;
            $wallet->forceFill(['balance_baisa' => $balance])->save();
            $movement = $wallet->movements()->create([
                'operation_key' => $operationKey,
                'type' => WalletMovementType::Purchase,
                'order_reference' => $orderReference,
                'credit_baisa' => 0,
                'debit_baisa' => $amountBaisa,
                'balance_after_baisa' => $balance,
                'metadata' => ['idempotency_key' => Str::uuid()->toString()],
            ]);

            return new WalletSpendData($wallet->id, $movement->id, $amountBaisa, $balance, $currency);
        });

        WalletMovementPosted::dispatch(WalletMovement::query()->findOrFail($spend->movementId));

        return $spend;
    }

    public function reversePurchase(string $orderReference): void
    {
        $postedMovement = null;

        DB::transaction(function () use ($orderReference, &$postedMovement): void {
            $purchase = WalletMovement::query()->where('operation_key', 'wallet-purchase:'.$orderReference)->first();

            if (! $purchase instanceof WalletMovement) {
                return;
            }

            $wallet = Wallet::query()->whereKey($purchase->wallet_id)->lockForUpdate()->firstOrFail();
            $operationKey = 'wallet-purchase-reversal:'.$orderReference;

            if (WalletMovement::query()->where('operation_key', $operationKey)->exists()) {
                $this->reverseSettlement($orderReference);

                return;
            }

            $allocations = WalletPurchaseAllocation::query()
                ->where('order_reference', $orderReference)
                ->whereNull('reversed_at')
                ->lockForUpdate()
                ->get();

            foreach ($allocations as $allocation) {
                $allocation->forceFill(['reversed_at' => now()])->save();

                $topUp = WalletTopUp::query()->whereKey($allocation->wallet_top_up_id)->lockForUpdate()->firstOrFail();
                $topUp->increment('spendable_baisa', $allocation->amount_baisa);

                if ($allocation->refund_deadline_at?->isFuture()) {
                    $topUp->increment('refundable_baisa', $allocation->amount_baisa);
                }
            }

            $balance = $wallet->balance_baisa + $purchase->debit_baisa;
            $wallet->forceFill(['balance_baisa' => $balance])->save();
            $postedMovement = $wallet->movements()->create([
                'operation_key' => $operationKey,
                'type' => WalletMovementType::PurchaseReversal,
                'order_reference' => $orderReference,
                'credit_baisa' => $purchase->debit_baisa,
                'debit_baisa' => 0,
                'balance_after_baisa' => $balance,
                'metadata' => ['reversed_movement_id' => $purchase->id],
            ]);
            $this->reverseSettlement($orderReference);
        });

        if ($postedMovement instanceof WalletMovement) {
            WalletMovementPosted::dispatch($postedMovement->refresh());
        }
    }

    public function refundTopUp(PaymentRefund $refund): void
    {
        if ($refund->state !== PaymentRefundState::Succeeded) {
            return;
        }

        $postedMovement = null;

        DB::transaction(function () use ($refund, &$postedMovement): void {
            $refund = PaymentRefund::query()
                ->whereKey($refund->getKey())
                ->with('payment')
                ->lockForUpdate()
                ->firstOrFail();
            $payment = $refund->payment;

            if ($payment->subject_type !== 'wallet_topup' || $payment->subject_reference === null) {
                return;
            }

            $topUp = WalletTopUp::query()
                ->where('reference', $payment->subject_reference)
                ->lockForUpdate()
                ->firstOrFail();
            $operationKey = 'wallet-top-up-refund:'.$refund->id;
            $existingMovement = WalletMovement::query()->where('operation_key', $operationKey)->first();

            if ($existingMovement instanceof WalletMovement) {
                return;
            }

            if (! in_array($topUp->status, [WalletTopUpStatus::Credited->value, WalletTopUpStatus::Refunding->value], true)) {
                throw ValidationException::withMessages(['refund' => 'The wallet top-up is not available for refund.']);
            }

            if ($topUp->refundable_baisa < $refund->amount_baisa
                || $topUp->reserved_refund_baisa < $refund->amount_baisa
                || $topUp->spendable_baisa < $refund->amount_baisa) {
                throw ValidationException::withMessages(['refund' => 'The wallet top-up no longer has enough reserved funds for this refund.']);
            }

            $wallet = Wallet::query()->whereKey($topUp->wallet_id)->lockForUpdate()->firstOrFail();

            if ($wallet->balance_baisa < $refund->amount_baisa) {
                throw ValidationException::withMessages(['refund' => 'The wallet balance is insufficient for this refund.']);
            }

            $wallet->forceFill(['balance_baisa' => $wallet->balance_baisa - $refund->amount_baisa])->save();
            $topUp->forceFill([
                'refundable_baisa' => $topUp->refundable_baisa - $refund->amount_baisa,
                'reserved_refund_baisa' => $topUp->reserved_refund_baisa - $refund->amount_baisa,
                'spendable_baisa' => $topUp->spendable_baisa - $refund->amount_baisa,
            ])->save();

            $postedMovement = $wallet->movements()->create([
                'wallet_top_up_id' => $topUp->id,
                'operation_key' => $operationKey,
                'type' => WalletMovementType::TopUpRefund,
                'credit_baisa' => 0,
                'debit_baisa' => $refund->amount_baisa,
                'balance_after_baisa' => $wallet->balance_baisa,
                'metadata' => ['payment_refund_id' => $refund->id, 'payment_reference' => $payment->reference],
            ]);

            $refundedBaisa = (int) PaymentRefund::query()
                ->where('payment_id', $payment->id)
                ->where('state', PaymentRefundState::Succeeded->value)
                ->sum('amount_baisa');

            $topUp->forceFill([
                'status' => $refundedBaisa >= $payment->amount_baisa
                    ? WalletTopUpStatus::Refunded
                    : WalletTopUpStatus::Credited,
            ])->save();
        });

        if ($postedMovement instanceof WalletMovement) {
            WalletMovementPosted::dispatch($postedMovement->refresh());
        }
    }

    public function markPurchaseEligible(string $orderReference): void
    {
        DB::transaction(function () use ($orderReference): void {
            $purchase = WalletMovement::query()
                ->where('operation_key', 'wallet-purchase:'.$orderReference)
                ->lockForUpdate()
                ->first();

            if (! $purchase instanceof WalletMovement || $purchase->type !== WalletMovementType::Purchase->value) {
                return;
            }

            $wallet = Wallet::query()->whereKey($purchase->wallet_id)->lockForUpdate()->firstOrFail();
            $settlement = WalletSettlement::query()->where('order_reference', $orderReference)->lockForUpdate()->first();

            if ($settlement instanceof WalletSettlement) {
                return;
            }

            WalletSettlement::query()->create([
                'wallet_id' => $wallet->id,
                'order_reference' => $orderReference,
                'amount_baisa' => $purchase->debit_baisa,
                'currency' => $wallet->currency,
                'status' => WalletSettlementStatus::Eligible,
                'eligible_at' => now(),
            ]);
        });
    }

    public function canClose(int $minorProfileId): bool
    {
        $hasOutstandingPaidOrder = Order::query()
            ->where('minor_profile_id', $minorProfileId)
            ->where('payment_method', 'wallet')
            ->whereNotNull('paid_at')
            ->whereIn('status', [
                OrderStatus::Confirmed->value,
                OrderStatus::Accepted->value,
                OrderStatus::Preparing->value,
                OrderStatus::ReadyForPickup->value,
            ])
            ->lockForUpdate()
            ->first(['id']) instanceof Order;

        if ($hasOutstandingPaidOrder) {
            return false;
        }

        $wallet = Wallet::query()->where('minor_profile_id', $minorProfileId)->lockForUpdate()->first();

        if (! $wallet instanceof Wallet) {
            return true;
        }

        if ($wallet->balance_baisa !== 0) {
            return false;
        }

        return ! $wallet->topUps()
            ->whereIn('status', [WalletTopUpStatus::Pending->value, WalletTopUpStatus::Credited->value])
            ->where(function ($query): void {
                $query->where('spendable_baisa', '>', 0)
                    ->orWhere('status', WalletTopUpStatus::Pending->value);
            })
            ->lockForUpdate()
            ->first() instanceof WalletTopUp;
    }

    private function reverseSettlement(string $orderReference): void
    {
        WalletSettlement::query()
            ->where('order_reference', $orderReference)
            ->where('status', WalletSettlementStatus::Eligible->value)
            ->update([
                'status' => WalletSettlementStatus::Reversed->value,
                'updated_at' => now(),
            ]);
    }
}
