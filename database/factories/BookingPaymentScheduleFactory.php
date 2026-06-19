<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingPaymentSchedule;
use App\Models\EventPaymentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingPaymentSchedule>
 */
class BookingPaymentScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'event_payment_plan_id' => EventPaymentPlan::factory(),
            'plan_name' => fake()->words(2, true),
            'currency' => 'OMR',
            'subtotal_baisa' => 0,
            'discount_amount_baisa' => 0,
            'total_baisa' => 0,
        ];
    }
}
