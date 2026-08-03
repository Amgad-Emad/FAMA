<?php

namespace App\Actions\Feed;

use App\Feed\Actor;
use App\Feed\FeedNotifier;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Toggle a like on a post or comment (feed-spec §2, ADR-F6/F7 counters via
 * LikeObserver). Idempotent and race-safe: the insert relies on the composite
 * unique index and treats a constraint violation as "already liked" — never a
 * read-then-write race. `onlyLike` makes it one-way (pending-intent replay and
 * the POST endpoint; DELETE un-likes).
 */
class ToggleLike
{
    public function __construct(private readonly FeedNotifier $notifier)
    {
    }

    /**
     * Like (or un-like) the target as the actor. Returns the resulting state
     * and the fresh counter.
     *
     * @return array{liked: bool, likes_count: int}
     */
    public function __invoke(Actor $actor, Post|Comment $target, bool $onlyLike = false, bool $onlyUnlike = false): array
    {
        $result = $this->toggle($actor, $target, $onlyLike, $onlyUnlike);

        if ($result['created']) {
            $this->notifier->liked($actor, $target);
        }

        return ['liked' => $result['liked'], 'likes_count' => $result['likes_count']];
    }

    /**
     * The transactional toggle, reporting whether a new like row landed.
     *
     * @return array{liked: bool, likes_count: int, created: bool}
     */
    private function toggle(Actor $actor, Post|Comment $target, bool $onlyLike, bool $onlyUnlike): array
    {
        return DB::transaction(function () use ($actor, $target, $onlyLike, $onlyUnlike): array {
            $keys = [
                'likeable_type' => $target->getMorphClass(),
                'likeable_id' => $target->getKey(),
                'liker_type' => $actor->getMorphClass(),
                'liker_id' => $actor->getKey(),
            ];

            $existing = Like::query()->where($keys)->first();

            if ($existing !== null) {
                if ($onlyLike) {
                    return $this->result($target, liked: true);
                }

                $existing->delete();

                return $this->result($target, liked: false);
            }

            if ($onlyUnlike) {
                return $this->result($target, liked: false);
            }

            $created = true;

            try {
                Like::query()->create($keys);
            } catch (UniqueConstraintViolationException) {
                // A concurrent request beat us to it — same outcome, no error.
                $created = false;
            }

            return $this->result($target, liked: true, created: $created);
        });
    }

    /**
     * Read the fresh denormalized counter after the observer ran.
     *
     * @return array{liked: bool, likes_count: int, created: bool}
     */
    private function result(Post|Comment $target, bool $liked, bool $created = false): array
    {
        return [
            'liked' => $liked,
            'created' => $created,
            'likes_count' => (int) $target->newQueryWithoutScopes()
                ->whereKey($target->getKey())
                ->value('likes_count'),
        ];
    }
}
