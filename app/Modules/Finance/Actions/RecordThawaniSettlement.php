<?php

namespace App\Modules\Finance\Actions;

use App\Enums\LedgerAccountType;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Modules\Finance\Models\FinancialEvent;
use App\Modules\Finance\Models\LedgerAccount;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Finance\Models\ThawaniSettlement;
use App\Modules\Finance\Models\ThawaniSettlementItem;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderRefundRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordThawaniSettlement
{
    public function __construct(private PostFinancialEvent $postFinancialEvent) {}

    /** @param array<int, array{kind: string, reference: string, amount_baisa: int}> $items */
    public function execute(string $providerReference, array $items, int $feeBaisa, int $bankNetBaisa, string $statementPath, int $actorUserId, Carbon $settledAt): ThawaniSettlement
    {
        if (blank($providerReference) || blank($statementPath) || $items === [] || $feeBaisa < 0 || $bankNetBaisa < 1) {
            throw ValidationException::withMessages(['settlement' => 'A settlement statement, references and valid amounts are required.']);
        }

        return DB::transaction(function () use ($providerReference, $items, $feeBaisa, $bankNetBaisa, $statementPath, $actorUserId, $settledAt): ThawaniSettlement {
            $actor = User::query()->findOrFail($actorUserId);
            if (! $actor->isPanelAdministrator() && ! $actor->can('Create:ThawaniSettlement')) {
                throw ValidationException::withMessages(['settlement' => 'This employee cannot record provider settlements.']);
            }
            if (ThawaniSettlement::query()->where('provider_reference', $providerReference)->exists()) {
                throw ValidationException::withMessages(['settlement' => 'Provider settlement reference was already recorded.']);
            }

            $grossBaisa = 0;
            $seen = [];
            foreach ($items as $item) {
                $reference = trim($item['reference']);
                $amountBaisa = (int) $item['amount_baisa'];
                if ($reference === '' || isset($seen[$reference]) || ThawaniSettlementItem::query()->where('provider_operation_reference', $reference)->exists()) {
                    throw ValidationException::withMessages(['settlement' => 'A statement operation reference is blank or repeated.']);
                }
                $seen[$reference] = true;
                if ($item['kind'] === 'payment') {
                    $payment = Payment::query()->where('provider_payment_id', $reference)->where('provider', 'thawani')->lockForUpdate()->first();
                    if (! $payment instanceof Payment || ! in_array($payment->state, [PaymentState::Paid, PaymentState::PartiallyRefunded, PaymentState::Refunded], true) || $amountBaisa !== $payment->amount_baisa) {
                        throw ValidationException::withMessages(['settlement' => 'Payment reference or amount does not match a confirmed Thawani payment.']);
                    }
                    $posted = $payment->subject_type === 'store_order'
                        ? FinancialEvent::query()->where('event_key', 'store-payment:'.Order::query()->where('reference', $payment->subject_reference)->value('id'))->exists()
                        : $payment->ledgerTransaction()->exists();
                    if (! $posted) {
                        throw ValidationException::withMessages(['settlement' => 'Payment Ledger entry is missing.']);
                    }
                } elseif ($item['kind'] === 'refund') {
                    $refund = PaymentRefund::query()->where('provider_refund_id', $reference)->where('state', PaymentRefundState::Succeeded->value)->lockForUpdate()->first();
                    if (! $refund instanceof PaymentRefund || $amountBaisa !== -$refund->amount_baisa) {
                        throw ValidationException::withMessages(['settlement' => 'Refund reference or amount does not match a confirmed Thawani refund.']);
                    }
                    $posted = $refund->payment->subject_type === 'store_order'
                        ? FinancialEvent::query()->where('event_key', 'store-refund:'.OrderRefundRequest::query()->where('payment_refund_id', $refund->id)->value('id'))->exists()
                        : $refund->ledgerTransaction()->exists();
                    if (! $posted) {
                        throw ValidationException::withMessages(['settlement' => 'Refund Ledger entry is missing.']);
                    }
                } else {
                    throw ValidationException::withMessages(['settlement' => 'Unknown settlement item kind.']);
                }
                $grossBaisa += $amountBaisa;
            }
            if ($grossBaisa < 1 || $grossBaisa - $feeBaisa !== $bankNetBaisa) {
                throw ValidationException::withMessages(['settlement' => 'Statement gross, fees and Bank amount do not reconcile.']);
            }

            $settlement = ThawaniSettlement::query()->create([
                'provider_reference' => $providerReference,
                'gross_baisa' => $grossBaisa,
                'fee_baisa' => $feeBaisa,
                'bank_net_baisa' => $bankNetBaisa,
                'currency' => 'OMR',
                'settled_at' => $settledAt,
                'statement_path' => $statementPath,
                'recorded_by_user_id' => $actorUserId,
            ]);
            foreach ($items as $item) {
                $settlement->items()->create(['provider_operation_reference' => trim($item['reference']), 'kind' => $item['kind'], 'amount_baisa' => $item['amount_baisa']]);
            }
            $lines = [
                ['code' => LedgerAccount::BANK_CODE, 'name' => 'Bank', 'type' => LedgerAccountType::Asset, 'debit' => $bankNetBaisa, 'credit' => 0],
                ['code' => LedgerAccount::THAWANI_CLEARING_CODE, 'name' => 'Thawani clearing', 'type' => LedgerAccountType::Asset, 'debit' => 0, 'credit' => $grossBaisa],
            ];
            if ($feeBaisa > 0) {
                $lines[] = ['code' => LedgerAccount::THAWANI_FEES_CODE, 'name' => 'Thawani fees', 'type' => LedgerAccountType::Expense, 'debit' => $feeBaisa, 'credit' => 0];
            }
            $this->postFinancialEvent->execute('thawani-settlement:'.$settlement->id, 'thawani_settlement', 'thawani_settlement', $settlement->id, 'Thawani settlement '.$providerReference, 'OMR', $settledAt, $lines);

            return $settlement;
        });
    }
}
