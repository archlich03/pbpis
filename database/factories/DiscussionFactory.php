<?php

namespace Database\Factories;

use App\Models\Discussion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discussion>
 */
class DiscussionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Discussion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => $this->faker->numberBetween(1, 100),
            'user_id' => $this->faker->numberBetween(1, 100),
            'parent_id' => null, // Top-level discussion by default
            'content' => $this->faker->paragraph,
            'ai_consent' => false, // Default to false
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the discussion has AI consent.
     */
    public function withAIConsent(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_consent' => true,
        ]);
    }

    /**
     * Indicate that the discussion is a reply to another discussion.
     */
    public function reply(int $parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Set a specific created_at timestamp.
     */
    public function createdAt($timestamp): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
