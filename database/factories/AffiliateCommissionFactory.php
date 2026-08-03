<?php

namespace Database\Factories;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliateReferral;
use App\Modules\Events\Models\Booking;
use App\Modules\Finance\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliateCommission>
 */
class AffiliateCommissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'affiliate_id' => Affiliate::factory(),
            'affiliate_referral_id' => AffiliateReferral::factory(),
            'booking_id' => Booking::factory(),
            'payment_id' => Payment::factory(),
            'base_amount_baisa' => 10000,
            'commission_rate_basis_points' => 0,
            'commission_amount_baisa' => 30000,
            'currency' => 'OMR',
            'earned_at' => now(),
        ];
    }
}
