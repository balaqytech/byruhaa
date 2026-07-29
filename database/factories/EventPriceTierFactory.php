<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventPriceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventPriceTier>
 */
class EventPriceTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->words(2, true),
            'position' => 1,
            'seat_capacity' => 10,
            'price_baisa' => 45000,
            'currency' => 'OMR',
            'is_active' => true,
        ];
    }
}
