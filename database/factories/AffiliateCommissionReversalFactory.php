<?php

namespace Database\Factories;

use App\Models\AffiliateCommissionReversal;
use App\Models\EventCancellation;
use App\Modules\Affiliates\Models\AffiliateCommission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliateCommissionReversal>
 */
class AffiliateCommissionReversalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'affiliate_commission_id' => AffiliateCommission::factory(),
            'event_cancellation_id' => EventCancellation::factory(),
            'amount_baisa' => 30000,
            'currency' => 'OMR',
            'reason' => fake()->sentence(),
            'reversed_at' => now(),
        ];
    }
}
