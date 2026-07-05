<?php

namespace Database\Factories;

use App\Models\CompCard;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompCard>
 */
class CompCardFactory extends Factory
{
    protected $model = CompCard::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'height_cm' => fake()->numberBetween(160, 195),
            'bust_cm' => fake()->numberBetween(78, 100),
            'waist_cm' => fake()->numberBetween(58, 80),
            'hips_cm' => fake()->numberBetween(85, 105),
            'shoe_size' => (string) fake()->numberBetween(36, 45),
            'dress_size' => fake()->randomElement(['XS', 'S', 'M', 'L']),
            'hair_color' => fake()->randomElement(['black', 'brown', 'blonde', 'auburn']),
            'eye_color' => fake()->randomElement(['brown', 'hazel', 'green', 'blue']),
            'skin_tone' => fake()->randomElement(['fair', 'olive', 'tan', 'deep']),
            'measurements' => ['inseam_cm' => fake()->numberBetween(70, 90)],
        ];
    }
}
