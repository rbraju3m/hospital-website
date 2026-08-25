<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\StaffRoles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

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
            /* Stated rather than left to the column default: a model created
               here does not carry the database's defaults back, so a factory
               user with no role can reach nothing and every panel test would
               fail on a 403 rather than on what it was testing. */
            'role' => StaffRoles::ADMINISTRATOR,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /** A receptionist: the front desk's work, and nothing editorial. */
    public function frontDesk(): static
    {
        return $this->state(['role' => StaffRoles::FRONT_DESK]);
    }

    /** An editor: the site's content, and no patient data. */
    public function editor(): static
    {
        return $this->state(['role' => StaffRoles::EDITOR]);
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
}
