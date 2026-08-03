<?php

namespace App\Observers;

use App\Models\Bookmark;
use App\Services\CounterService;

/**
 * Keeps posts.bookmarks_count in lockstep with the bookmarks rows (ADR-F6) —
 * inside the writing transaction, like every other feed counter.
 */
class BookmarkObserver
{
    public function __construct(private readonly CounterService $counters)
    {
    }

    /**
     * A save landed.
     */
    public function created(Bookmark $bookmark): void
    {
        $this->counters->applyBookmarkDelta($bookmark, 1);
    }

    /**
     * A save was withdrawn.
     */
    public function deleted(Bookmark $bookmark): void
    {
        $this->counters->applyBookmarkDelta($bookmark, -1);
    }
}
