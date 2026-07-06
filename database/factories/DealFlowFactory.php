<?php

namespace Database\Factories;

use App\Models\DealFlow;
use Database\Seeders\DealFlowSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealFlow>
 */
class DealFlowFactory extends Factory
{
    protected $model = DealFlow::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => str($name)->slug().'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->sentence(),
            'applies_to' => null,
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }

    /**
     * Attach the canonical Standard Booking steps (see DealFlowSeeder).
     */
    public function standard(): static
    {
        return $this->afterCreating(function (DealFlow $flow): void {
            foreach (DealFlowSeeder::STANDARD_STEPS as $position => $step) {
                $flow->steps()->create($step + ['position' => $position]);
            }
        });
    }
}
