<?php

namespace Database\Factories;

use App\Models\Speciality;
use App\Models\TalentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Speciality>
 */
class SpecialityFactory extends Factory
{
    protected $model = Speciality::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Unique per (skill, slug) — the factory must not collide when several
        // specialities are made under one skill in the same test.
        $name = ucfirst($this->faker->unique()->words(2, true));

        return [
            'talent_type_id' => TalentType::factory(),
            'name' => ['en' => $name, 'ar' => $name],
            'slug' => Str::slug($name),
            'is_regional' => false,
            'is_active' => true,
            'position' => 0,
        ];
    }

    /**
     * Added for the regional market specifically.
     */
    public function regional(): static
    {
        return $this->state(fn () => ['is_regional' => true]);
    }

    /**
     * Retired from the pickers; existing claims keep rendering.
     */
    public function retired(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
