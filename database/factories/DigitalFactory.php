<?php

namespace Database\Factories;

use App\Models\Digital;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Digital>
 */
class DigitalFactory extends Factory
{
    protected $model = Digital::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'shot_type' => fake()->randomElement(['front', 'side', 'back', 'full', 'headshot', 'smile']),
            'captured_at' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
