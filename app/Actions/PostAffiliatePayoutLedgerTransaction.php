<?php

namespace App\Actions;

use App\Enums\AffiliatePayoutRequestStatus;
use App\Enums\LedgerAccountType;
use App\Models\AffiliatePayoutRequest;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PostAffiliatePayoutLedgerTransaction
{
    public function execute(AffiliatePayoutRequest $payoutRequest): LedgerTransaction
    {
        return DB::transaction(function () use ($payoutRequest): LedgerTransaction {
            $payoutRequest = AffiliatePayoutRequest::query()
                ->whereKey($payoutRequest->id)
                ->with('affiliate')
                ->lockForUpdate()
                ->firstOrFail();

            if ($payoutRequest->status !== AffiliatePayoutRequestStatus::Paid) {
                throw new RuntimeException('Only paid affiliate payout requests can be posted to the ledger.');
            }

            $existingTransaction = $payoutRequest->ledgerTransaction()->with('entries')->first();

            if ($existingTransaction) {
                return $existingTransaction;
            }

            $liabilityAccount = $this->account(
                LedgerAccount::AFFILIATE_COMMISSION_LIABILITY_CODE,
                'Affiliate commission liability',
                LedgerAccountType::Liability,
                $payoutRequest->currency,
            );

            $clearingAccount = $this->account(
                LedgerAccount::AFFILIATE_PAYOUT_CLEARING_CODE,
                'Affiliate payout clearing',
                LedgerAccountType::Asset,
                $payoutRequest->currency,
            );

            $transaction = $payoutRequest->ledgerTransaction()->create([
                'reference' => 'LED-'.$payoutRequest->reference,
                'description' => 'Affiliate payout '.$payoutRequest->reference,
                'occurred_at' => $payoutRequest->paid_at ?? now(),
                'currency' => $payoutRequest->currency,
                'total_baisa' => $payoutRequest->amount_baisa,
            ]);

            $memo = $this->memo($payoutRequest);
            $transaction->entries()->createMany([
                [
                    'ledger_account_id' => $liabilityAccount->id,
                    'debit_baisa' => $payoutRequest->amount_baisa,
                    'credit_baisa' => 0,
                    'currency' => $payoutRequest->currency,
                    'memo' => $memo,
                ],
                [
                    'ledger_account_id' => $clearingAccount->id,
                    'debit_baisa' => 0,
                    'credit_baisa' => $payoutRequest->amount_baisa,
                    'currency' => $payoutRequest->currency,
                    'memo' => $memo,
                ],
            ]);

            $transaction->load('entries');

            if ((int) $transaction->entries->sum('debit_baisa') !== (int) $transaction->entries->sum('credit_baisa')) {
                throw new RuntimeException('Affiliate payout ledger transaction is not balanced.');
            }

            return $transaction;
        });
    }

    private function account(string $code, string $name, LedgerAccountType $type, string $currency): LedgerAccount
    {
        return LedgerAccount::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'type' => $type,
                'currency' => $currency,
                'is_active' => true,
            ],
        );
    }

    private function memo(AffiliatePayoutRequest $payoutRequest): string
    {
        return Str::limit('Affiliate '.$payoutRequest->affiliate->code.' payout '.$payoutRequest->reference, 255, '');
    }
}
