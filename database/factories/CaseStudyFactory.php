<?php

namespace Database\Factories;

use App\Models\CaseStudy;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseStudy>
 */
class CaseStudyFactory extends Factory
{
    protected $model = CaseStudy::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'title' => ['en' => fake()->catchPhrase(), 'ar' => 'دراسة حالة'],
            'client_name' => fake()->company(),
            'role' => ['en' => fake()->jobTitle(), 'ar' => 'الدور'],
            'summary' => ['en' => fake()->sentence(), 'ar' => 'ملخص'],
            'body' => ['en' => fake()->paragraphs(3, true), 'ar' => 'المحتوى الكامل'],
            'results' => ['reach' => fake()->numberBetween(10000, 5000000), 'roi' => fake()->numberBetween(2, 12).'x'],
            'year' => fake()->numberBetween(2019, 2026),
            'url' => fake()->optional()->url(),
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
