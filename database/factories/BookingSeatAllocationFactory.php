<?php

namespace Database\Factories;

use App\Enums\SeatAllocationState;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingSeatAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingSeatAllocation>
 */
class BookingSeatAllocationFactory extends Factory
{
    protected $model = BookingSeatAllocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'event_id' => fn (array $attributes): int => (int) Booking::query()
                ->whereKey($attributes['booking_id'])
                ->value('event_id'),
            'seat_count' => 1,
            'state' => SeatAllocationState::Held,
            'held_at' => now(),
            'hold_expires_at' => now()->addMinutes(15),
        ];
    }
}
