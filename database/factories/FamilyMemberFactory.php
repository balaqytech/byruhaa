<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\FamilyMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyMember>
 */
class FamilyMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => fake()->name(),
            'birth_date' => now()->subYears(fake()->numberBetween(9, 16))->subDays(fake()->numberBetween(0, 300)),
            'school_name' => fake()->company().' School',
            'grade' => (string) fake()->numberBetween(4, 10),
            'medical_notes' => null,
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => '+9689'.fake()->numerify('#######'),
        ];
    }
}
