<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\BrandCreativeNeed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandCreativeNeed>
 */
class BrandCreativeNeedFactory extends Factory
{
    protected $model = BrandCreativeNeed::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'project_frequency' => fake()->randomElement(['occasional', 'monthly', 'weekly', 'ongoing']),
            'budget_tier' => fake()->randomElement(['under_500', '500_2000', '2000_10000', '10000_plus']),
        ];
    }
}
