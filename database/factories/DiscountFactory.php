<?php

namespace Database\Factories;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'event_id' => null,
            'amount_baisa' => fake()->numberBetween(1000, 10000),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
            'minimum_family_members' => null,
            'maximum_family_members' => null,
            'is_active' => true,
        ];
    }
}
