<?php

namespace App\Notifications;

use App\Models\Deal;
use Illuminate\Notifications\Notification;

/**
 * Notifies the talent that a brand has just started a deal with them (via the
 * "Start a deal" CTA or an enquiry conversion), so it surfaces as actionable in
 * their inbox. Same `database` channel + payload contract as the other deal
 * notifications; `type` is `deal.started`.
 */
class DealStarted extends Notification
{
    public function __construct(private readonly Deal $deal) {}

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
            'type' => 'deal.started',
            'deal_id' => $this->deal->id,
            'deal_reference' => $this->deal->reference,
            'deal_title' => $this->deal->title,
            'message' => "A brand started a deal with you: {$this->deal->title}.",
        ];
    }
}
