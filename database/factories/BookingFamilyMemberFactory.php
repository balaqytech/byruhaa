<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Modules\Identity\Models\FamilyMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingFamilyMember>
 */
class BookingFamilyMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'family_member_id' => FamilyMember::factory(),
        ];
    }
}
