<?php

namespace Database\Factories;

use App\Models\Affiliate;
use App\Models\AffiliateReferral;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliateReferral>
 */
class AffiliateReferralFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $capturedAt = now()->subDay();

        return [
            'affiliate_id' => Affiliate::factory(),
            'booking_id' => Booking::factory(),
            'affiliate_code' => fake()->unique()->bothify('REF####'),
            'affiliate_name' => fake()->name(),
            'captured_at' => $capturedAt,
            'expires_at' => $capturedAt->copy()->addDays(90),
            'attributed_at' => now(),
        ];
    }
}
