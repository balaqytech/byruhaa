<?php

namespace Database\Factories;

use App\Models\BookingFamilyMember;
use App\Models\EventContract;
use App\States\Contract\AwaitingSignature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventContract>
 */
class EventContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_family_member_id' => BookingFamilyMember::factory(),
            'state' => AwaitingSignature::$name,
            'contract_html' => '<p>Sample contract terms.</p>',
        ];
    }
}
