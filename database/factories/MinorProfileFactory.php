<?php

namespace Database\Factories;

use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<MinorProfile> */
class MinorProfileFactory extends Factory
{
    protected $model = MinorProfile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'family_member_id' => FamilyMember::factory(),
            'member_code' => 'BRH-'.strtoupper(fake()->unique()->bothify('######??')),
            'password' => Hash::make('password'),
            'status' => MinorProfileStatus::Active,
            'direct_payment_enabled' => false,
            'activated_at' => now(),
        ];
    }
}
