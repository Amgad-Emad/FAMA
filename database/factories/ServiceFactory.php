<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'name' => ['en' => fake()->randomElement(['Half-day shoot', 'Full-day shoot', 'Editorial', 'Campaign']), 'ar' => 'خدمة'],
            'description' => ['en' => fake()->sentence(), 'ar' => 'وصف الخدمة'],
            'price' => fake()->randomFloat(2, 500, 20000),
            'currency' => 'EGP',
            'price_unit' => fake()->randomElement(['hour', 'day', 'project', 'fixed']),
            'duration_minutes' => fake()->optional()->numberBetween(60, 600),
            'is_active' => true,
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
