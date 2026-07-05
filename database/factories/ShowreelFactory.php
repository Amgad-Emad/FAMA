<?php

namespace Database\Factories;

use App\Models\Showreel;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Showreel>
 */
class ShowreelFactory extends Factory
{
    protected $model = Showreel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'title' => ['en' => fake()->words(3, true), 'ar' => 'ريل'],
            'video_url' => 'https://vimeo.com/'.fake()->numerify('#########'),
            'platform' => fake()->randomElement(['youtube', 'vimeo', 'self_hosted']),
            'duration_seconds' => fake()->numberBetween(30, 300),
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
