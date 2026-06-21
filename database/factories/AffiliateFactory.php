<?php

namespace Database\Factories;

use App\Enums\AffiliateStatus;
use App\Models\Affiliate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Affiliate>
 */
class AffiliateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => '+9689'.fake()->unique()->numerify('#######'),
            'password' => 'password',
            'code' => Str::upper(fake()->unique()->bothify('????####')),
            'status' => AffiliateStatus::Approved,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => AffiliateStatus::Pending,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'review_notes' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => AffiliateStatus::Rejected,
            'reviewed_at' => now(),
            'review_notes' => 'Rejected',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => [
            'status' => AffiliateStatus::Suspended,
            'reviewed_at' => now(),
            'review_notes' => 'Suspended',
        ]);
    }
}
