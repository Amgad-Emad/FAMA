<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandReview>
 */
class BrandReviewFactory extends Factory
{
    protected $model = BrandReview::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'talent_id' => Talent::factory(),
            'deal_id' => null,
            'communication_rating' => fake()->numberBetween(3, 5),
            'fairness_rating' => fake()->numberBetween(3, 5),
            'creative_respect_rating' => fake()->numberBetween(3, 5),
            'body' => fake()->sentence(),
            'is_approved' => true,
            'status' => 'approved',
        ];
    }

    /** Awaiting moderation. */
    public function pending(): static
    {
        return $this->state(fn () => ['is_approved' => false, 'status' => 'pending']);
    }
}
