<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Modules\Identity\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouponRedemption>
 */
class CouponRedemptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coupon_id' => Coupon::factory(),
            'booking_id' => Booking::factory(),
            'customer_id' => Customer::factory(),
            'redeemed_at' => now(),
            'released_at' => null,
        ];
    }

    public function released(): static
    {
        return $this->state(fn (array $attributes): array => [
            'released_at' => now(),
        ]);
    }
}
