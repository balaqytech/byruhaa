<?php

namespace Database\Factories;

use App\Models\LedgerTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LedgerTransaction>
 */
class LedgerTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'LED-'.Str::upper(Str::random(12)),
            'description' => fake()->sentence(),
            'occurred_at' => now(),
            'currency' => 'OMR',
            'total_baisa' => 1000,
        ];
    }
}
