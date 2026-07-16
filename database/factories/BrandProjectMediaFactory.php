<?php

namespace Database\Factories;

use App\Models\BrandProject;
use App\Models\BrandProjectMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandProjectMedia>
 */
class BrandProjectMediaFactory extends Factory
{
    protected $model = BrandProjectMedia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'brand_project_id' => BrandProject::factory(),
            'media_type' => 'image',
            'embed_url' => null,
            'caption' => ['en' => fake()->words(2, true), 'ar' => 'تعليق'],
            'position' => 0,
        ];
    }
}
