<?php

namespace App\Notifications\Credits;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Base for the collaborator-credit notifications (ADR-AA).
 *
 * Deliberately carries the SAME payload shape as FeedNotification — {message
 * (an English key rendered through __() at read time), params, url, actor} —
 * so credit notifications land in the one notification inbox and
 * `NotificationResource` renders them without a special case. A talent should
 * not have to look in two places to find out someone credited them.
 *
 * WATCH OUT: never redeclare `$afterCommit` — Queueable owns it, and the
 * resulting fatal is invisible under the pao output wrapper.
 */
abstract class CreditNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }
}
