<?php

namespace Database\Factories;

use App\Enums\EventCancellationStatus;
use App\Models\EventCancellation;
use App\Modules\Events\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventCancellation>
 */
class EventCancellationFactory extends Factory
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
            'cancelled_by_user_id' => null,
            'status' => EventCancellationStatus::Pending,
            'reason' => fake()->sentence(),
            'currency' => 'OMR',
            'bookings_count' => 0,
            'payments_count' => 0,
            'refundable_amount_baisa' => 0,
            'refunded_payments_count' => 0,
            'refunded_amount_baisa' => 0,
            'requested_at' => now(),
        ];
    }
}
