<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\BrandSignal;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandSignal>
 */
class BrandSignalFactory extends Factory
{
    protected $model = BrandSignal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'talent_id' => Talent::factory(),
            'action_type' => fake()->randomElement(['view', 'save', 'brief_sent', 'profile_open']),
            'context' => null,
        ];
    }
}
