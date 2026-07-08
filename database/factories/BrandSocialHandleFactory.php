<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\BrandSocialHandle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandSocialHandle>
 */
class BrandSocialHandleFactory extends Factory
{
    protected $model = BrandSocialHandle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $handle = fake()->userName();

        return [
            'brand_id' => Brand::factory(),
            'platform' => fake()->randomElement(['instagram', 'tiktok', 'x', 'linkedin', 'youtube', 'facebook', 'behance', 'website', 'other']),
            'handle' => '@'.$handle,
            'url' => 'https://instagram.com/'.$handle,
            'position' => 0,
        ];
    }
}
