<?php

namespace Database\Factories;

use App\Models\Body;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Body>
 */
class BodyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Body::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'body_id' => Str::uuid(),
            'title' => fake()->company(),
            'classification' => 'SPK',
            'chairman_id' => fake()->numberBetween(1, 3),
            'members' => [1,2,3],
            'is_ba_sp' => fake()->boolean(),
        ];
    }
}

