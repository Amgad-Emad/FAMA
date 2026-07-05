<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'reviewer_name' => fake()->name(),
            'reviewer_role' => fake()->jobTitle(),
            'reviewer_company' => fake()->company(),
            'rating' => fake()->numberBetween(3, 5),
            'body' => fake()->paragraph(),
            'project_type' => fake()->randomElement(['editorial', 'campaign', 'lookbook', 'social']),
            'is_approved' => true,
            'reviewed_at' => now(),
        ];
    }

    /**
     * A pending (unmoderated) review.
     */
    public function pending(): static
    {
        return $this->state(fn (): array => ['is_approved' => false]);
    }
}
