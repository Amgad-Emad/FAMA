<?php

namespace App\Notifications;

use App\Models\Deal;
use Illuminate\Notifications\Notification;

/**
 * Notifies the other party when a chat message is posted to a deal thread.
 * Stored on the `database` channel; the `data` payload is the notification
 * contract the mobile app renders.
 */
class NewDealMessage extends Notification
{
    public function __construct(
        private readonly Deal $deal,
        private readonly string $senderRole,
        private readonly string $preview,
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
        return [
            'type' => 'deal.message',
            'deal_id' => $this->deal->id,
            'deal_reference' => $this->deal->reference,
            'deal_title' => $this->deal->title,
            'from_role' => $this->senderRole,
            'preview' => str($this->preview)->limit(120)->value(),
            'message' => "New message on {$this->deal->title}.",
        ];
    }
}
