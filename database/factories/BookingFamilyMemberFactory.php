<?php

namespace Database\Factories;

use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingFamilyMember;
use App\Modules\Identity\Models\FamilyMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingFamilyMember>
 */
class BookingFamilyMemberFactory extends Factory
{
    protected $model = BookingFamilyMember::class;

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
