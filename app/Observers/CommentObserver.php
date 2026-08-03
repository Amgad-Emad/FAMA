<?php

namespace App\Observers;

use App\Models\Comment;
use App\Services\CounterService;

/**
 * Keeps posts.comments_count (and the parent's replies_count for replies) in
 * lockstep with the comments rows (ADR-F6). Soft delete decrements; restore
 * re-increments — a tombstoned comment no longer counts.
 */
class CommentObserver
{
    public function __construct(private readonly CounterService $counters)
    {
    }

    /**
     * A comment landed — bump the post (and parent, for replies).
     */
    public function created(Comment $comment): void
    {
        $this->counters->applyCommentDelta($comment, 1);
    }

    /**
     * A comment was deleted (soft or force) — drop the counters.
     */
    public function deleted(Comment $comment): void
    {
        $this->counters->applyCommentDelta($comment, -1);
    }

    /**
     * A soft-deleted comment was restored — re-add it to the counters.
     */
    public function restored(Comment $comment): void
    {
        $this->counters->applyCommentDelta($comment, 1);
    }
}
