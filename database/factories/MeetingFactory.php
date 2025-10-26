<?php

namespace Database\Factories;

use App\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Meeting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Str::uuid(),
            'status' => 'Suplanuotas',
            'secretary_id' => fake()->numberBetween(1, 3),
            'body_id' => \App\Models\Body::factory(),
            'is_evote' => fake()->boolean(),
            'meeting_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'vote_start' => fake()->dateTimeBetween('-2 years', '-1 years'),
            'vote_end' => fake()->dateTimeBetween('-1 years', 'now'),
        ];
    }
}

