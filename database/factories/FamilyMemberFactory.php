<?php

namespace Database\Factories;

use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyMember>
 */
class FamilyMemberFactory extends Factory
{
    protected $model = FamilyMember::class;

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
            'relationship_to_customer' => fake()->randomElement(['Son', 'Daughter', 'Sibling', 'Relative']),
        ];
    }
}
