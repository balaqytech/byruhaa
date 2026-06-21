<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
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
            'civil_id' => fake()->unique()->numerify('########'),
            'address' => fake()->streetAddress(),
            'wilaya' => fake()->city(),
            'area' => fake()->streetName(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'additional_info' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function phoneOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => null,
        ]);
    }

    public function incompleteProfile(): static
    {
        return $this->state(fn (array $attributes) => [
            'civil_id' => null,
            'address' => null,
            'wilaya' => null,
            'area' => null,
        ]);
    }
}
