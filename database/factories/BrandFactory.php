<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'name' => $name,
            'slug' => str($name)->slug().'-'.fake()->unique()->numberBetween(1, 99999),
            'is_complete' => true,
            'is_active' => true,
            'is_verified' => false,
            'is_published' => false,
        ];
    }

    /**
     * A brand that has not finished onboarding (cannot start a deal).
     */
    public function incomplete(): static
    {
        return $this->state(fn () => ['is_complete' => false]);
    }
}
