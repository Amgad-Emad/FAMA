<?php

namespace App\Actions\Feed;

use App\Models\Post;
use Illuminate\Support\Facades\DB;

/**
 * Pin/unpin a post on its author's presence (feed-spec §4). Maximum ONE
 * pinned post per talent — pinning a new post silently unpins the previous
 * one inside the same transaction.
 */
class TogglePinPost
{
    /**
     * Toggle the pin and return the resulting state.
     *
     * @return array{pinned: bool}
     */
    public function __invoke(Post $post): array
    {
        return DB::transaction(function () use ($post): array {
            if ($post->is_pinned) {
                $post->forceFill(['is_pinned' => false])->save();

                return ['pinned' => false];
            }

            Post::query()
                ->where('author_type', $post->author_type)
                ->where('author_id', $post->author_id)
                ->where('is_pinned', true)
                ->update(['is_pinned' => false]);

            $post->forceFill(['is_pinned' => true])->save();

            return ['pinned' => true];
        });
    }
}
