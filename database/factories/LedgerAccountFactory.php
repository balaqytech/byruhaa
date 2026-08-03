<?php

namespace Database\Factories;

use App\Enums\LedgerAccountType;
use App\Modules\Finance\Models\LedgerAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LedgerAccount>
 */
class LedgerAccountFactory extends Factory
{
    protected $model = LedgerAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => '9999-'.Str::upper(Str::random(8)),
            'name' => fake()->words(2, true),
            'type' => LedgerAccountType::Asset,
            'currency' => 'OMR',
            'is_active' => true,
        ];
    }
}
