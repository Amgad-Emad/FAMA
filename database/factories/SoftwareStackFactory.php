<?php

namespace Database\Factories;

use App\Models\SoftwareStack;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoftwareStack>
 */
class SoftwareStackFactory extends Factory
{
    protected $model = SoftwareStack::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'software_name' => fake()->randomElement(['Figma', 'Photoshop', 'Illustrator', 'After Effects', 'Lightroom', 'Premiere Pro']),
            'proficiency' => fake()->randomElement(['beginner', 'intermediate', 'advanced', 'expert']),
            'position' => fake()->numberBetween(0, 15),
        ];
    }
}
