<?php

namespace Database\Factories;

use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ledger_transaction_id' => LedgerTransaction::factory(),
            'ledger_account_id' => LedgerAccount::factory(),
            'debit_baisa' => 1000,
            'credit_baisa' => 0,
            'currency' => 'OMR',
        ];
    }
}
