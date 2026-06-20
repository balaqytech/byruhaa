<?php

namespace Database\Factories;

use App\Enums\BookingInstallmentState;
use App\Models\BookingInstallment;
use App\Models\BookingPaymentSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingInstallment>
 */
class BookingInstallmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_payment_schedule_id' => BookingPaymentSchedule::factory(),
            'name' => fake()->word(),
            'sequence' => 1,
            'percentage' => 100,
            'due_date' => now()->addWeek()->toDateString(),
            'gross_amount_baisa' => 0,
            'discount_amount_baisa' => 0,
            'amount_baisa' => 0,
            'currency' => 'OMR',
            'state' => BookingInstallmentState::Pending,
        ];
    }
}
