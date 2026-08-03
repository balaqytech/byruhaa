<?php

namespace Database\Factories;

use App\Enums\PaymentState;
use App\Models\BookingInstallment;
use App\Modules\Finance\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_installment_id' => BookingInstallment::factory(),
            'provider' => 'thawani',
            'reference' => 'PAY-'.Str::upper(Str::random(12)),
            'amount_baisa' => 1000,
            'currency' => 'OMR',
            'state' => PaymentState::Pending,
        ];
    }
}
