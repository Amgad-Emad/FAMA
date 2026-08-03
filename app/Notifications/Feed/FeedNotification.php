<?php

namespace App\Notifications\Feed;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Base for the eight feed notifications (feed-spec §9): database channel,
 * queued after the writing transaction commits, carrying a LOCALIZABLE
 * payload — {message (an English key rendered through __() in the viewer's
 * locale at read time), params, url (the deep link to the post/comment
 * anchor), actor} — never pre-rendered text.
 */
abstract class FeedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<string, mixed> $payload
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
