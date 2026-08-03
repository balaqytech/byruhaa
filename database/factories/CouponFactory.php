<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Modules\Events\Models\Coupon;
use App\Modules\Events\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'code' => Str::upper(fake()->bothify('SAVE##')),
            'name' => fake()->words(3, true),
            'type' => CouponType::FixedAmountPerMember,
            'amount_baisa' => fake()->numberBetween(1000, 10000),
            'percentage_basis_points' => null,
            'currency' => 'OMR',
            'expires_at' => now()->addWeek(),
            'minimum_family_members' => 1,
            'maximum_family_members' => null,
            'maximum_uses' => null,
            'maximum_uses_per_customer' => null,
            'is_active' => true,
        ];
    }

    public function percentage(int $basisPoints = 1000): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => CouponType::PercentagePerMember,
            'amount_baisa' => null,
            'percentage_basis_points' => $basisPoints,
        ]);
    }
}
