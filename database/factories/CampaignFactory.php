<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucwords(fake()->unique()->words(3, true));

        return [
            'brand_id' => Brand::factory(),
            'title' => $title,
            'slug' => str($title)->slug().'-'.fake()->unique()->numberBetween(1, 99999),
            'type' => fake()->randomElement(['campaign', 'shoot']),
            'description' => ['en' => fake()->sentence(), 'ar' => 'وصف الحملة'],
            'status' => 'draft',
            'budget_min' => 5000,
            'budget_max' => 25000,
            'currency' => 'EGP',
            'location_city' => 'Cairo',
            'location_country' => 'Egypt',
            'start_date' => null,
            'end_date' => null,
            'is_public' => true,
            'positions_count' => fake()->numberBetween(1, 4),
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => ['status' => 'open']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }
}
