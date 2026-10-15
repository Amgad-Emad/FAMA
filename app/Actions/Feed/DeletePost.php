<?php

namespace App\Actions\Feed;

use App\Models\Post;
use Illuminate\Support\Facades\DB;

/**
 * Author-side post deletion (D12): a soft delete inside a transaction —
 * PostObserver uncounts it (ADR-F6) and soft-deletes its comments with it
 * (App\Feed\PostPurger). Files stay for the restore window; after it,
 * media:purge-trashed removes the post for good with its comments, likes,
 * saves, mentions, links and files.
 */
class DeletePost
{
    /**
     * Soft-delete the post.
     */
    public function __invoke(Post $post): void
    {
        DB::transaction(function () use ($post): void {
            $post->delete();
        });
    }
}
