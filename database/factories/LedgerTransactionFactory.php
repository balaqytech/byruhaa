<?php

namespace Database\Factories;

use App\Modules\Finance\Models\LedgerTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerTransaction>
 */
class LedgerTransactionFactory extends Factory
{
    protected $model = LedgerTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(),
            'occurred_at' => now(),
            'currency' => 'OMR',
            'total_baisa' => 1000,
        ];
    }
}
