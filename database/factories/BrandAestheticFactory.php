<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\BrandAesthetic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandAesthetic>
 */
class BrandAestheticFactory extends Factory
{
    protected $model = BrandAesthetic::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'brand_references' => fake()->optional()->sentence(),
        ];
    }
}
