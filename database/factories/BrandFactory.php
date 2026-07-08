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
            'description' => ['en' => fake()->catchPhrase(), 'ar' => 'علامة تجارية إبداعية'],
            'industry' => fake()->randomElement(['fashion', 'beauty', 'food_beverage', 'lifestyle', 'tech', 'other']),
            'brand_stage' => fake()->randomElement(['new', 'growing', 'established']),
            'base_city' => fake()->randomElement(['Cairo', 'Alexandria', 'Giza', 'Dubai']),
            'base_country' => fake()->randomElement(['Egypt', 'Egypt', 'UAE']),
            'geographic_reach' => fake()->randomElement(['same_city', 'mena', 'international']),
            'founded_year' => fake()->optional()->numberBetween(2005, 2024),
            'company_size' => fake()->randomElement(['solo', 'small', 'medium', 'large', 'enterprise']),
            'website' => fake()->optional()->url(),
            'is_complete' => true,
            'is_active' => true,
            'is_verified' => false,
            'is_published' => true,
        ];
    }

    /** Onboarding not finished — cannot transact and is hidden. */
    public function incomplete(): static
    {
        return $this->state(fn () => ['is_complete' => false, 'is_published' => false]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['is_verified' => true]);
    }
}
