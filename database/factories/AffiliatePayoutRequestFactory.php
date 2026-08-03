<?php

namespace Database\Factories;

use App\Enums\AffiliatePayoutRequestStatus;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Models\AffiliatePayoutRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AffiliatePayoutRequest>
 */
class AffiliatePayoutRequestFactory extends Factory
{
    protected $model = AffiliatePayoutRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'affiliate_id' => Affiliate::factory(),
            'reference' => 'APO-'.Str::upper(Str::random(12)),
            'amount_baisa' => 20000,
            'currency' => 'OMR',
            'status' => AffiliatePayoutRequestStatus::Pending,
            'affiliate_notes' => fake()->sentence(),
            'payment_details' => [
                'method' => 'bank_transfer',
                'account' => fake()->numerify('########'),
            ],
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => AffiliatePayoutRequestStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => AffiliatePayoutRequestStatus::Paid,
            'approved_at' => now()->subHour(),
            'paid_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => AffiliatePayoutRequestStatus::Rejected,
            'rejected_at' => now(),
        ]);
    }
}
