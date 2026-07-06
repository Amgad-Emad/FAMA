<?php

namespace Database\Factories;

use App\Models\DealFlow;
use App\Models\DealFlowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealFlowStep>
 */
class DealFlowStepFactory extends Factory
{
    protected $model = DealFlowStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deal_flow_id' => DealFlow::factory(),
            'key' => fake()->unique()->word(),
            'name' => ucwords(fake()->words(2, true)),
            'instructions' => fake()->sentence(),
            'actor' => 'talent',
            'step_type' => 'form',
            'position' => 0,
            'is_required' => true,
            'is_skippable' => false,
            'settings' => [],
        ];
    }
}
