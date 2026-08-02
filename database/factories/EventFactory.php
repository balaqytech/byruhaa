<?php

namespace Database\Factories;

use App\Enums\EventEnrollmentStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->sentence(3);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'type' => fake()->randomElement(['trip', 'camp', 'festival']),
            'status' => EventStatus::Published,
            'enrollment_status' => EventEnrollmentStatus::BookingOpen,
            'subtitle' => fake()->sentence(4),
            'excerpt' => fake()->sentence(),
            'card_topics' => [fake()->word(), fake()->word()],
            'description_html' => '<p>'.fake()->paragraph().'</p>',
            'contract_terms_html' => '<p>'.fake()->paragraph().'</p>',
            'location' => fake()->city(),
            'schedule_text' => fake()->sentence(5),
            'starts_at' => now()->addWeeks(2),
            'ends_at' => now()->addWeeks(2)->addHours(6),
            'minimum_age' => 9,
            'maximum_age' => 16,
            'seat_capacity' => 30,
            'price_baisa' => 0,
            'currency' => 'OMR',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Draft,
        ]);
    }
}
