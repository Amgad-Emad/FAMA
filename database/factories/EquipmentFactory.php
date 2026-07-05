<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'category' => fake()->randomElement(['camera', 'lens', 'lighting', 'audio', 'grip', 'drone', 'accessory']),
            'brand' => fake()->randomElement(['RED', 'Sony', 'Canon', 'ARRI', 'Aputure', 'DJI']),
            'model' => fake()->bothify('??-###'),
            'name' => fake()->words(2, true),
            'notes' => ['en' => fake()->optional()->sentence(), 'ar' => 'ملاحظات'],
            'position' => fake()->numberBetween(0, 30),
        ];
    }
}
