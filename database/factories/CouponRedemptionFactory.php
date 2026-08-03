<?php

namespace Database\Factories;

use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\Coupon;
use App\Modules\Events\Models\CouponRedemption;
use App\Modules\Identity\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CouponRedemption>
 */
class CouponRedemptionFactory extends Factory
{
    protected $model = CouponRedemption::class;

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
