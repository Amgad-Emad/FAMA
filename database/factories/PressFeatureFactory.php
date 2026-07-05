<?php

namespace Database\Factories;

use App\Models\PressFeature;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PressFeature>
 */
class PressFeatureFactory extends Factory
{
    protected $model = PressFeature::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'publication' => fake()->randomElement(['Vogue Arabia', 'GQ Middle East', 'Harper’s Bazaar', 'Campaign ME']),
            'title' => fake()->sentence(6),
            'url' => fake()->url(),
            'published_date' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
