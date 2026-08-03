<?php

namespace App\Observers;

use App\Models\Like;
use App\Services\CounterService;

/**
 * Keeps the likeable's likes_count in lockstep with the likes rows (ADR-F6).
 * Observer events fire synchronously inside the writing transaction, so the
 * counter and the row commit or roll back together.
 */
class LikeObserver
{
    public function __construct(private readonly CounterService $counters)
    {
    }

    /**
     * A like landed — bump the target's counter.
     */
    public function created(Like $like): void
    {
        $this->counters->applyLikeDelta($like, 1);
    }

    /**
     * A like was withdrawn — drop the target's counter.
     */
    public function deleted(Like $like): void
    {
        $this->counters->applyLikeDelta($like, -1);
    }
}
