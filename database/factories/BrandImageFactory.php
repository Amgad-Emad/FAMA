<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\BrandImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandImage>
 */
class BrandImageFactory extends Factory
{
    protected $model = BrandImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'position' => 0,
        ];
    }
}
