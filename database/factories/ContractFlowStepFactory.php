<?php

namespace Database\Factories;

use App\Models\ContractFlow;
use App\Models\ContractFlowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractFlowStep>
 */
class ContractFlowStepFactory extends Factory
{
    protected $model = ContractFlowStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_flow_id' => ContractFlow::factory(),
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
