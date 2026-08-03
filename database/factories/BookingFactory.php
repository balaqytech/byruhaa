<?php

namespace Database\Factories;

use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\Event;
use App\Modules\Events\States\Booking\PendingReview;
use App\Modules\Identity\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'event_id' => Event::factory(),
            'state' => PendingReview::$name,
            'currency' => 'OMR',
        ];
    }
}
