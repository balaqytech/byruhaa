<?php

namespace Database\Factories;

use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $staffRole = Role::findOrCreate('staff', 'web');

            if ($staffRole->permissions()->doesntExist()) {
                $staffRole->givePermissionTo(collect(config('filament-shield.staff_permissions'))
                    ->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'))
                    ->all());
            }

            $user->assignRole($staffRole);
        });
    }

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
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => []);
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->syncRoles(Role::findOrCreate(config('filament-shield.super_admin.name'), 'web'));
        });
    }
}
