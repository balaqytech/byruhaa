<?php

namespace App\Actions;

use App\Enums\AffiliatePayoutRequestStatus;
use App\Enums\LedgerAccountType;
use App\Models\AffiliateCommissionReversal;
use App\Models\EventCancellation;
use App\Modules\Affiliates\Models\AffiliateCommission;
use App\Modules\Finance\Models\LedgerAccount;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReverseAffiliateCommission
{
    public function execute(AffiliateCommission $commission, EventCancellation $cancellation): AffiliateCommissionReversal
    {
        return DB::transaction(function () use ($commission, $cancellation): AffiliateCommissionReversal {
            $commission = AffiliateCommission::query()->whereKey($commission->id)->lockForUpdate()->firstOrFail();
            $reversal = AffiliateCommissionReversal::query()->firstOrCreate(
                ['affiliate_commission_id' => $commission->id],
                [
                    'event_cancellation_id' => $cancellation->id,
                    'amount_baisa' => $commission->commission_amount_baisa,
                    'currency' => $commission->currency,
                    'reason' => 'Event cancellation: '.$cancellation->reason,
                    'reversed_at' => now(),
                ],
            );

            if ($reversal->ledgerTransaction()->exists()) {
                return $reversal;
            }

            $liability = $this->account(LedgerAccount::AFFILIATE_COMMISSION_LIABILITY_CODE, 'Affiliate commission liability', LedgerAccountType::Liability, $commission->currency);
            $expense = $this->account(LedgerAccount::AFFILIATE_COMMISSION_EXPENSE_CODE, 'Affiliate commission expense', LedgerAccountType::Expense, $commission->currency);
            $transaction = $reversal->ledgerTransaction()->create([
                'reference' => 'LED-AFCR-'.$reversal->id,
                'description' => 'Affiliate commission reversal for cancelled event',
                'occurred_at' => $reversal->reversed_at,
                'currency' => $reversal->currency,
                'total_baisa' => $reversal->amount_baisa,
            ]);
            $transaction->entries()->createMany([
                ['ledger_account_id' => $liability->id, 'debit_baisa' => $reversal->amount_baisa, 'credit_baisa' => 0, 'currency' => $reversal->currency, 'memo' => $reversal->reason],
                ['ledger_account_id' => $expense->id, 'debit_baisa' => 0, 'credit_baisa' => $reversal->amount_baisa, 'currency' => $reversal->currency, 'memo' => $reversal->reason],
            ]);

            if ($transaction->entries()->sum('debit_baisa') !== $transaction->entries()->sum('credit_baisa')) {
                throw new RuntimeException('Affiliate commission reversal ledger transaction is not balanced.');
            }

            $this->rejectUnfundedPayoutRequests($commission);

            return $reversal->refresh();
        });
    }

    private function rejectUnfundedPayoutRequests(AffiliateCommission $commission): void
    {
        $affiliate = $commission->affiliate()->firstOrFail();
        $committedBaisa = $affiliate->requestedPayoutBaisa();
        $earnedBaisa = $affiliate->earnedCommissionBaisa();

        if ($committedBaisa <= $earnedBaisa) {
            return;
        }

        $requests = $affiliate->payoutRequests()
            ->whereIn('status', [AffiliatePayoutRequestStatus::Pending->value, AffiliatePayoutRequestStatus::Approved->value])
            ->latest('id')
            ->get();

        foreach ($requests as $request) {
            if ($committedBaisa <= $earnedBaisa) {
                break;
            }

            $request->forceFill([
                'status' => AffiliatePayoutRequestStatus::Rejected,
                'admin_notes' => trim(($request->admin_notes ? $request->admin_notes."\n" : '').'Automatically rejected after an event cancellation reversed the related commission.'),
                'rejected_at' => now(),
            ])->save();
            $committedBaisa -= $request->amount_baisa;
        }
    }

    private function account(string $code, string $name, LedgerAccountType $type, string $currency): LedgerAccount
    {
        return LedgerAccount::query()->firstOrCreate(['code' => $code], [
            'name' => $name,
            'type' => $type,
            'currency' => $currency,
            'is_active' => true,
        ]);
    }
}
