<?php

namespace App\Actions\Feed;

use App\Feed\Actor;
use App\Models\Follow;
use App\Models\Talent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Toggle a follow edge (feed-spec §2). Allowed edges: talent → talent and
 * brand → talent; talent → brand only behind feed.allow_following_brands.
 * Self-follow is a domain violation (422 via the InvalidArgumentException
 * renderer). Race-safe through the composite unique index; counters move via
 * FollowObserver inside this transaction (ADR-F6).
 */
class ToggleFollow
{
    public function __construct(private readonly \App\Feed\FeedNotifier $notifier)
    {
    }

    /**
     * Follow (or unfollow) the followable as the actor. Returns the resulting
     * edge state and the followable's fresh followers_count when it has one.
     *
     * @return array{following: bool, followers_count: int|null}
     */
    public function __invoke(Actor $follower, Actor $followable, bool $onlyFollow = false, bool $onlyUnfollow = false): array
    {
        $this->assertAllowedEdge($follower, $followable);

        $existedBefore = Follow::query()->where([
            'follower_type' => $follower->getMorphClass(),
            'follower_id' => $follower->getKey(),
            'followable_type' => $followable->getMorphClass(),
            'followable_id' => $followable->getKey(),
        ])->exists();

        $result = DB::transaction(function () use ($follower, $followable, $onlyFollow, $onlyUnfollow): array {
            $keys = [
                'follower_type' => $follower->getMorphClass(),
                'follower_id' => $follower->getKey(),
                'followable_type' => $followable->getMorphClass(),
                'followable_id' => $followable->getKey(),
            ];

            $existing = Follow::query()->where($keys)->first();

            if ($existing !== null) {
                if ($onlyFollow) {
                    return $this->result($followable, following: true);
                }

                $existing->delete();

                return $this->result($followable, following: false);
            }

            if ($onlyUnfollow) {
                return $this->result($followable, following: false);
            }

            try {
                Follow::query()->create($keys + ['created_at' => now()]);
            } catch (UniqueConstraintViolationException) {
                // Concurrent double-submit — same outcome.
            }

            return $this->result($followable, following: true);
        });

        if ($result['following'] && ! $existedBefore) {
            $this->notifier->followed($follower, $followable);
        }

        // A follow changes what should be suggested next (5Z).
        app(\App\Services\SuggestionService::class)->forget($follower);

        return $result;
    }

    /**
     * Enforce the follow-graph rules: no self-follow, and only the edges the
     * product allows (feed-spec §2).
     */
    private function assertAllowedEdge(Actor $follower, Actor $followable): void
    {
        if ($follower->getMorphClass() === $followable->getMorphClass()
            && (string) $follower->getKey() === (string) $followable->getKey()) {
            throw new InvalidArgumentException(__('You cannot follow yourself.'));
        }

        $followableIsTalent = $followable instanceof Talent;

        if (! $followableIsTalent && ! config('feed.allow_following_brands')) {
            throw new InvalidArgumentException(__('Brands cannot be followed.'));
        }
    }

    /**
     * Read the followable's fresh counter after the observer ran.
     *
     * @return array{following: bool, followers_count: int|null}
     */
    private function result(Actor $followable, bool $following): array
    {
        $count = (int) $followable->newQueryWithoutScopes()
            ->whereKey($followable->getKey())
            ->value('followers_count');

        return ['following' => $following, 'followers_count' => $count];
    }
}
