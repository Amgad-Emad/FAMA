<?php

namespace App\Listeners;

use App\Feed\Actor;
use App\Feed\PendingIntent;
use Illuminate\Auth\Events\Login;

/**
 * Completes the guest-intent loop (ADR-F8): when a session that captured a
 * feed intent authenticates as an Actor (talent or brand), the intent replays
 * exactly once — the like/follow/comment has already landed when the user
 * returns to the post. Admin logins clear nothing and replay nothing.
 */
class ReplayPendingIntent
{
    public function __construct(private readonly PendingIntent $pendingIntent)
    {
    }

    /**
     * Replay the stored intent as the freshly-authenticated actor.
     */
    public function handle(Login $event): void
    {
        if (! $event->user instanceof Actor) {
            return;
        }

        $this->pendingIntent->replay($event->user);
    }
}
