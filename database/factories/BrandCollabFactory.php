<?php

namespace Database\Factories;

use App\Models\BrandCollab;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandCollab>
 */
class BrandCollabFactory extends Factory
{
    protected $model = BrandCollab::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'brand_name' => fake()->company(),
            'project_title' => ['en' => fake()->catchPhrase(), 'ar' => 'مشروع'],
            'year' => fake()->numberBetween(2018, 2026),
            'url' => fake()->optional()->url(),
            'position' => fake()->numberBetween(0, 20),
        ];
    }
}
