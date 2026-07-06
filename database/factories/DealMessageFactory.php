<?php

namespace Database\Factories;

use App\Models\Deal;
use App\Models\DealMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealMessage>
 */
class DealMessageFactory extends Factory
{
    protected $model = DealMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deal_id' => Deal::factory(),
            'deal_step_id' => null,
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
