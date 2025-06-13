<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class VartotojaiFactory extends Factory
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
            'naudotojo_id' => fake()->unique()->numberBetween(1, 1000),
            'ms_id' => '',
            'password' => static::$password ??= Hash::make('password'),
            'vardas' => fake()->firstName(),
            'pavarde' => fake()->lastName(),
            'el_pastas' => fake()->unique()->safeEmail(),
            'role' => 'IT administratorius',
            'pedagoginis_vardas' => 'Doc., dr.',
            'lytis' => true,
            'prisijungimo_statusas' => false,
            'paskutinis_prisijungimas' => now(),
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
}

