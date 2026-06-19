<?php

namespace Database\Factories;

use App\Enums\PaymentRefundState;
use App\Models\Payment;
use App\Models\PaymentRefund;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentRefund>
 */
class PaymentRefundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'reference' => 'REF-'.Str::upper(Str::random(12)),
            'amount_baisa' => 1000,
            'currency' => 'OMR',
            'state' => PaymentRefundState::Pending,
            'reason' => 'Customer refund',
        ];
    }
}
