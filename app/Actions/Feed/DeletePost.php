<?php

namespace App\Actions\Feed;

use App\Models\Post;
use Illuminate\Support\Facades\DB;

/**
 * Author-side post deletion (D12): a soft delete inside a transaction —
 * PostObserver uncounts it (ADR-F6); comments stay in place (hidden with the
 * post, preserved for a restore); media and mention/link rows remain attached
 * to the soft-deleted row.
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
