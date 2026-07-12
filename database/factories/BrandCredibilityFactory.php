<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\BrandCredibility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandCredibility>
 */
class BrandCredibilityFactory extends Factory
{
    protected $model = BrandCredibility::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'completed_projects_count' => fake()->numberBetween(0, 40),
            'avg_response_time_hours' => fake()->randomFloat(2, 1, 48),
            'response_rate_pct' => fake()->numberBetween(60, 100),
            'brief_quality_score' => fake()->randomFloat(2, 2, 5),
        ];
    }
}
