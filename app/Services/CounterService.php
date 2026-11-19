<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Brand;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\Talent;
use App\States\Post\Published;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Owns every denormalized feed counter (ADR-F6).
 *
 * All mutation goes through here, and only from observers — so a counter moves
 * inside the SAME transaction as the row that justifies it (observers fire
 * synchronously within the writing transaction). Decrements clamp at zero
 * (unsigned columns; strict MySQL would error on underflow). The nightly
 * feed:reconcile-counters command calls reconcile() to recompute from source
 * and repair any drift.
 */
class CounterService extends Service
{
    protected string $logChannel = 'feed';

    /**
     * Apply a like's effect on its likeable's likes_count.
     */
    public function applyLikeDelta(Like $like, int $delta): void
    {
        $likeable = $like->likeable()->withoutGlobalScopes()->first();

        if ($likeable !== null) {
            $this->adjust($likeable, 'likes_count', $delta);
        }
    }

    /**
     * Apply a comment's effect: the post's comments_count always, plus the
     * parent's replies_count when the comment is a reply.
     */
    public function applyCommentDelta(Comment $comment, int $delta): void
    {
        $post = Post::withTrashed()->find($comment->post_id);

        if ($post !== null) {
            $this->adjust($post, 'comments_count', $delta);
        }

        if ($comment->parent_id !== null) {
            $parent = Comment::withTrashed()->find($comment->parent_id);

            if ($parent !== null) {
                $this->adjust($parent, 'replies_count', $delta);
            }
        }
    }

    /**
     * Apply a follow edge's effect: the followable's followers_count and the
     * follower's following_count.
     */
    public function applyFollowDelta(Follow $follow, int $delta): void
    {
        $followable = $follow->followable()->withoutGlobalScopes()->first();
        $follower = $follow->follower()->withoutGlobalScopes()->first();

        // Talents AND brands carry followers_count since 5Z flipped
        // feed.allow_following_brands on.
        if ($followable instanceof Talent || $followable instanceof Brand) {
            $this->adjust($followable, 'followers_count', $delta);
        }

        if ($follower !== null) {
            $this->adjust($follower, 'following_count', $delta);
        }
    }

    /**
     * Apply a bookmark's effect on its post's bookmarks_count (5X).
     */
    public function applyBookmarkDelta(Bookmark $bookmark, int $delta): void
    {
        if ($bookmark->bookmarkable_type !== 'post') {
            return;
        }

        $post = Post::withTrashed()->find($bookmark->bookmarkable_id);

        if ($post !== null) {
            $this->adjust($post, 'bookmarks_count', $delta);
        }
    }

    /**
     * Apply a published post's effect on its author's posts_count AND the
     * denormalized last_posted_at (the FOLLOWING surface's "quiet since"
     * signal): a publish stamps the later of the current value and this
     * post's published_at; leaving the published state (delete, moderation
     * hide/remove) recomputes from the remaining published posts so removing
     * the LAST post clears the column. Both writes bypass model events, like
     * every counter move.
     */
    public function applyPostPublishDelta(Post $post, int $delta): void
    {
        $author = $post->author()->withoutGlobalScopes()->first();

        if (! $author instanceof Talent) {
            return;
        }

        $this->adjust($author, 'posts_count', $delta);

        if ($delta > 0) {
            DB::table('talents')->where('id', $author->id)->update([
                'last_posted_at' => DB::raw(
                    'GREATEST(COALESCE(last_posted_at, "1970-01-01"), '
                    .DB::getPdo()->quote(($post->published_at ?? now())->toDateTimeString()).')'
                ),
            ]);
        } else {
            DB::table('talents')->where('id', $author->id)->update([
                'last_posted_at' => DB::raw(
                    "(SELECT MAX(posts.published_at) FROM posts
                      WHERE posts.author_type = 'talent' AND posts.author_id = talents.id
                        AND posts.state = '".Published::$name."' AND posts.deleted_at IS NULL)"
                ),
            ]);
        }
    }

    /**
     * Recompute every denormalized counter from source and repair drift.
     * Returns a map of "table.column" => rows repaired, for reporting.
     *
     * @return array<string, int>
     */
    public function reconcile(): array
    {
        $repaired = [];

        $repaired['posts.likes_count'] = $this->reconcileColumn(
            'posts', 'likes_count',
            "SELECT COUNT(*) FROM likes WHERE likes.likeable_type = 'post' AND likes.likeable_id = posts.id"
        );

        $repaired['posts.bookmarks_count'] = $this->reconcileColumn(
            'posts', 'bookmarks_count',
            "SELECT COUNT(*) FROM bookmarks WHERE bookmarks.bookmarkable_type = 'post' AND bookmarks.bookmarkable_id = posts.id"
        );

        $repaired['posts.comments_count'] = $this->reconcileColumn(
            'posts', 'comments_count',
            'SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id AND comments.deleted_at IS NULL'
        );

        $repaired['projects.likes_count'] = $this->reconcileColumn(
            'projects', 'likes_count',
            "SELECT COUNT(*) FROM likes WHERE likes.likeable_type = 'project' AND likes.likeable_id = projects.id"
        );

        $repaired['comments.likes_count'] = $this->reconcileColumn(
            'comments', 'likes_count',
            "SELECT COUNT(*) FROM likes WHERE likes.likeable_type = 'comment' AND likes.likeable_id = comments.id"
        );

        // Self-referential — MySQL can't subquery the table being updated
        // (error 1093), so replies_count reconciles through a derived table.
        $repaired['comments.replies_count'] = DB::update(
            'UPDATE comments
                LEFT JOIN (
                    SELECT parent_id, COUNT(*) AS cnt
                    FROM comments
                    WHERE parent_id IS NOT NULL AND deleted_at IS NULL
                    GROUP BY parent_id
                ) AS replies ON replies.parent_id = comments.id
             SET comments.replies_count = COALESCE(replies.cnt, 0)
             WHERE comments.replies_count <> COALESCE(replies.cnt, 0)'
        );

        $repaired['talents.followers_count'] = $this->reconcileColumn(
            'talents', 'followers_count',
            "SELECT COUNT(*) FROM follows WHERE follows.followable_type = 'talent' AND follows.followable_id = talents.id"
        );

        $repaired['talents.following_count'] = $this->reconcileColumn(
            'talents', 'following_count',
            "SELECT COUNT(*) FROM follows WHERE follows.follower_type = 'talent' AND follows.follower_id = talents.id"
        );

        $repaired['talents.posts_count'] = $this->reconcileColumn(
            'talents', 'posts_count',
            "SELECT COUNT(*) FROM posts WHERE posts.author_type = 'talent' AND posts.author_id = talents.id"
            ." AND posts.state = '".Published::$name."' AND posts.deleted_at IS NULL"
        );

        // last_posted_at is a nullable timestamp, not a count — the repair
        // needs MySQL's NULL-safe <=> instead of reconcileColumn's <>.
        $lastPostedSql = "SELECT MAX(posts.published_at) FROM posts
            WHERE posts.author_type = 'talent' AND posts.author_id = talents.id
              AND posts.state = '".Published::$name."' AND posts.deleted_at IS NULL";
        $repaired['talents.last_posted_at'] = DB::update(
            "UPDATE talents SET last_posted_at = ({$lastPostedSql})
             WHERE NOT (last_posted_at <=> ({$lastPostedSql}))"
        );

        $repaired['brands.followers_count'] = $this->reconcileColumn(
            'brands', 'followers_count',
            "SELECT COUNT(*) FROM follows WHERE follows.followable_type = 'brand' AND follows.followable_id = brands.id"
        );

        $repaired['brands.following_count'] = $this->reconcileColumn(
            'brands', 'following_count',
            "SELECT COUNT(*) FROM follows WHERE follows.follower_type = 'brand' AND follows.follower_id = brands.id"
        );

        return $repaired;
    }

    /**
     * Adjust one counter column by a delta, clamping at zero, bypassing model
     * events and timestamps (a counter move is not an "update" of the row).
     */
    private function adjust(Model $model, string $column, int $delta): void
    {
        DB::table($model->getTable())
            ->where($model->getKeyName(), $model->getKey())
            ->update([$column => DB::raw("GREATEST(CAST({$column} AS SIGNED) + ({$delta}), 0)")]);
    }

    /**
     * Set a counter column to its recomputed source-of-truth value wherever it
     * drifted, returning how many rows needed repair.
     */
    private function reconcileColumn(string $table, string $column, string $countSql): int
    {
        return DB::table($table)
            ->whereRaw("{$column} <> ({$countSql})")
            ->update([$column => DB::raw("({$countSql})")]);
    }
}
