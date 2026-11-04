<?php

namespace App\Modules\Finance\Commands;

use App\Modules\Finance\Models\Wallet;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('wallets:reconcile {--limit=100 : Maximum wallets to inspect}')]
#[Description('Check wallet balances against their immutable movement history')]
class ReconcileWalletBalances extends Command
{
    public function handle(): int
    {
        if (! config('byruhaa.wallets.enabled', false)) {
            $this->info('Wallets are disabled.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $discrepancies = 0;

        Wallet::query()->oldest('id')->limit($limit)->each(function (Wallet $wallet) use (&$discrepancies): void {
            $expectedBalance = (int) $wallet->movements()
                ->selectRaw('COALESCE(SUM(credit_baisa - debit_baisa), 0) as balance')
                ->value('balance');

            if ($expectedBalance === $wallet->balance_baisa) {
                return;
            }

            $discrepancies++;
            $this->warn("Wallet {$wallet->id} has balance {$wallet->balance_baisa}; movement history totals {$expectedBalance}.");
        });

        if ($discrepancies > 0) {
            $this->error("Found {$discrepancies} wallet balance discrepancy(ies).");

            return self::FAILURE;
        }

        $this->info('Wallet balances match their movement history.');

        return self::SUCCESS;
    }
}
