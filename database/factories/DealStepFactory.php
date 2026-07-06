<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\DealStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealStep>
 */
class DealStepFactory extends Factory
{
    protected $model = DealStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deal_id' => Deal::factory(),
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
