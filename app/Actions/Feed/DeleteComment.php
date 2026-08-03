<?php

namespace App\Actions\Feed;

use App\Models\Comment;
use Illuminate\Support\Facades\DB;

/**
 * Delete a comment (D12): a soft delete — CommentObserver uncounts it; a
 * deleted comment WITH replies keeps rendering as a tombstone so the thread
 * survives (the listing includes trashed parents that still have replies).
 */
class DeleteComment
{
    /**
     * Soft-delete the comment.
     */
    public function __invoke(Comment $comment): void
    {
        DB::transaction(function () use ($comment): void {
            $comment->delete();
        });
    }
}
