<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractStep>
 */
class ContractStepFactory extends Factory
{
    protected $model = ContractStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'flow_step_id' => null,
            'key' => fake()->unique()->word(),
            'name' => ucwords(fake()->words(2, true)),
            'actor' => 'talent',
            'step_type' => 'form',
            'position' => 0,
            'status' => 'pending',
            'payload' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function awaitingAction(): static
    {
        return $this->state(fn () => ['status' => 'awaiting_action']);
    }
}
