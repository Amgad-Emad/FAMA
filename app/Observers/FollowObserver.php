<?php

namespace App\Observers;

use App\Models\Follow;
use App\Services\CounterService;

/**
 * Keeps followers_count / following_count in lockstep with the follows rows
 * (ADR-F6). Follows are append-only — created and deleted are the only events.
 */
class FollowObserver
{
    public function __construct(private readonly CounterService $counters)
    {
    }

    /**
     * A follow edge was created.
     */
    public function created(Follow $follow): void
    {
        $this->counters->applyFollowDelta($follow, 1);
    }

    /**
     * A follow edge was removed (unfollow).
     */
    public function deleted(Follow $follow): void
    {
        $this->counters->applyFollowDelta($follow, -1);
    }
}
