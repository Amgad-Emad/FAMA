<?php

namespace Database\Factories;

use App\Models\ContractFlow;
use Database\Seeders\ContractFlowSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractFlow>
 */
class ContractFlowFactory extends Factory
{
    protected $model = ContractFlow::class;

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
     * Attach the canonical Standard Booking steps (see ContractFlowSeeder).
     */
    public function standard(): static
    {
        return $this->afterCreating(function (ContractFlow $flow): void {
            foreach (ContractFlowSeeder::STANDARD_STEPS as $position => $step) {
                $flow->steps()->create($step + ['position' => $position]);
            }
        });
    }
}
