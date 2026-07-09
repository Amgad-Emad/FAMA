<?php

namespace App\Notifications;

use App\Models\Deal;
use Illuminate\Notifications\Notification;

/**
 * Notifies the party whose turn it now is on a deal (after a step advances,
 * rejects or is skipped). Stored on the `database` channel — the mobile app reads
 * it from GET /api/v1/notifications. The `data` payload is the notification
 * contract (stable keys the app renders).
 */
class DealTurnChanged extends Notification
{
    public function __construct(
        private readonly Deal $deal,
        private readonly string $recipientRole,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * The stored contract.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $step = $this->deal->currentStep;

        return [
            'type' => 'deal.turn',
            'deal_id' => $this->deal->id,
            'deal_reference' => $this->deal->reference,
            'deal_title' => $this->deal->title,
            'role' => $this->recipientRole,
            'step_key' => $step?->key,
            'step_name' => $step?->name,
            'message' => "It's your turn on {$this->deal->title}.",
        ];
    }
}
