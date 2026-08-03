<?php

namespace App\Actions\Feed;

use App\Feed\Actor;
use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Toggle a save (5X). Idempotent and race-safe on the unique index. The
 * bookmarkable morph is open by design, but ONLY posts are enabled this
 * phase — anything else answers with a clear message (extending to talents /
 * Projects later is deleting a line here, not a migration — ADR-F9).
 */
class ToggleBookmark
{
    /**
     * Save (or unsave) the target as the actor.
     *
     * @return array{saved: bool, bookmarks_count: int}
     */
    public function __invoke(Actor $actor, Model $bookmarkable, bool $onlySave = false, bool $onlyUnsave = false): array
    {
        if (! $bookmarkable instanceof Post) {
            throw new InvalidArgumentException(__('Only posts can be saved for now.'));
        }

        return DB::transaction(function () use ($actor, $bookmarkable, $onlySave, $onlyUnsave): array {
            $keys = [
                'bookmarker_type' => $actor->getMorphClass(),
                'bookmarker_id' => $actor->getKey(),
                'bookmarkable_type' => $bookmarkable->getMorphClass(),
                'bookmarkable_id' => $bookmarkable->getKey(),
            ];

            $existing = Bookmark::query()->where($keys)->first();

            if ($existing !== null) {
                if ($onlySave) {
                    return $this->result($bookmarkable, saved: true);
                }

                $existing->delete();

                return $this->result($bookmarkable, saved: false);
            }

            if ($onlyUnsave) {
                return $this->result($bookmarkable, saved: false);
            }

            try {
                Bookmark::query()->create($keys + ['created_at' => now()]);
            } catch (UniqueConstraintViolationException) {
                // Concurrent double-submit — same outcome.
            }

            return $this->result($bookmarkable, saved: true);
        });
    }

    /**
     * The fresh counter after the observer ran.
     *
     * @return array{saved: bool, bookmarks_count: int}
     */
    private function result(Post $post, bool $saved): array
    {
        return [
            'saved' => $saved,
            'bookmarks_count' => (int) $post->newQueryWithoutScopes()
                ->whereKey($post->getKey())
                ->value('bookmarks_count'),
        ];
    }
}
