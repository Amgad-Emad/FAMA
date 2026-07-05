<?php

namespace Database\Factories;

use App\Models\LookType;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LookType>
 */
class LookTypeFactory extends Factory
{
    protected $model = LookType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $look = fake()->randomElement(['Editorial', 'Commercial', 'Runway', 'Fitness', 'Beauty']);

        return [
            'talent_id' => Talent::factory(),
            'name' => ['en' => $look, 'ar' => 'إطلالة'],
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
