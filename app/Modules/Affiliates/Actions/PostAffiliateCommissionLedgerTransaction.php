<?php

namespace App\Modules\Affiliates\Actions;

use App\Enums\LedgerAccountType;
use App\Modules\Affiliates\Models\AffiliateCommission;
use App\Modules\Finance\Models\LedgerAccount;
use App\Modules\Finance\Models\LedgerTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PostAffiliateCommissionLedgerTransaction
{
    public function execute(AffiliateCommission $commission): LedgerTransaction
    {
        return DB::transaction(function () use ($commission): LedgerTransaction {
            $commission = AffiliateCommission::query()
                ->whereKey($commission->id)
                ->with(['affiliate', 'booking', 'payment'])
                ->lockForUpdate()
                ->firstOrFail();

            $existingTransaction = $commission->ledgerTransaction()->with('entries')->first();

            if ($existingTransaction) {
                return $existingTransaction;
            }

            $expenseAccount = $this->account(
                LedgerAccount::AFFILIATE_COMMISSION_EXPENSE_CODE,
                'Affiliate commission expense',
                LedgerAccountType::Expense,
                $commission->currency,
            );

            $liabilityAccount = $this->account(
                LedgerAccount::AFFILIATE_COMMISSION_LIABILITY_CODE,
                'Affiliate commission liability',
                LedgerAccountType::Liability,
                $commission->currency,
            );

            $transaction = $commission->ledgerTransaction()->create([
                'reference' => 'LED-AFC-'.$commission->id,
                'description' => 'Affiliate commission '.$commission->affiliate->code.' for '.$commission->payment->reference,
                'occurred_at' => $commission->earned_at,
                'currency' => $commission->currency,
                'total_baisa' => $commission->commission_amount_baisa,
            ]);

            $memo = $this->memo($commission);
            $transaction->entries()->createMany([
                [
                    'ledger_account_id' => $expenseAccount->id,
                    'debit_baisa' => $commission->commission_amount_baisa,
                    'credit_baisa' => 0,
                    'currency' => $commission->currency,
                    'memo' => $memo,
                ],
                [
                    'ledger_account_id' => $liabilityAccount->id,
                    'debit_baisa' => 0,
                    'credit_baisa' => $commission->commission_amount_baisa,
                    'currency' => $commission->currency,
                    'memo' => $memo,
                ],
            ]);

            $transaction->load('entries');

            if ((int) $transaction->entries->sum('debit_baisa') !== (int) $transaction->entries->sum('credit_baisa')) {
                throw new RuntimeException('Affiliate commission ledger transaction is not balanced.');
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

    private function memo(AffiliateCommission $commission): string
    {
        return Str::limit('Affiliate '.$commission->affiliate->code.' booking '.$commission->booking->reference.' payment '.$commission->payment->reference, 255, '');
    }
}
