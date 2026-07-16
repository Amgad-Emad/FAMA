<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractMessage>
 */
class ContractMessageFactory extends Factory
{
    protected $model = ContractMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'contract_step_id' => null,
            'sender_type' => null,
            'sender_id' => null,
            'sender_role' => 'talent',
            'type' => 'message',
            'body' => fake()->sentence(),
            'attachments' => null,
            'status' => 'sent',
        ];
    }

    public function systemEvent(): static
    {
        return $this->state(fn () => ['type' => 'system_event', 'sender_role' => 'system']);
    }
}
