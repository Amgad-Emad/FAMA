<?php

namespace App\Feed;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

/**
 * What happens to a post's children when the post is deleted.
 *
 *  - Soft delete (author or staff): its comments are soft-deleted with it, in
 *    the same moment, so they leave every list (the admin Comments tab
 *    included) instead of lingering on a post nobody can see.
 *  - Purge (media:purge-trashed, after the restore window): the post is
 *    removed for good — likes, saves, mentions and link chips of the post AND
 *    its comments (polymorphic rows, which no database cascade reaches), then
 *    the post itself; medialibrary deletes its files, and the foreign keys
 *    cascade comments, skill tags and seen-marks. Reports stay as moderation
 *    history (their target shows as gone).
 */
class PostPurger
{
    /** Soft-delete the post's live comments, stamped with the post's deletion time. */
    public function softDeleteComments(Post $post): int
    {
        return Comment::query()->where('post_id', $post->getKey())->whereNull('deleted_at')
            ->update(['deleted_at' => $post->deleted_at ?? now(), 'updated_at' => now()]);
    }

    /** Remove a (trashed) post and everything hanging off it, permanently. */
    public function purge(Post $post): void
    {
        DB::transaction(function () use ($post): void {
            $commentIds = Comment::withTrashed()->where('post_id', $post->getKey())->pluck('id')->all();
            $targets = [['post', [$post->getKey()]], ['comment', $commentIds]];

            foreach ($targets as [$type, $ids]) {
                if ($ids === []) {
                    continue;
                }
                DB::table('likes')->where('likeable_type', $type)->whereIn('likeable_id', $ids)->delete();
                DB::table('mentions')->where('mentionable_type', $type)->whereIn('mentionable_id', $ids)->delete();
                DB::table('linked_urls')->where('linkable_type', $type)->whereIn('linkable_id', $ids)->delete();
            }
            DB::table('bookmarks')->where('bookmarkable_type', 'post')->where('bookmarkable_id', $post->getKey())->delete();

            // Eloquent force delete: medialibrary's hook deletes the files; FKs
            // cascade comments, post_skill and feed_seen.
            $post->forceDelete();
        });
    }
}
