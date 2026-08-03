<?php

namespace App\Services;

use App\Feed\Actor;
use App\Models\Follow;
use Illuminate\Support\Collection;

/**
 * Follow-graph reads (feed-spec §2). The bulk isFollowing() map is THE way a
 * page resolves follow buttons — one query for the whole set, keyed
 * "type:id"; no per-row lookups anywhere.
 */
class FollowService extends Service
{
    protected string $logChannel = 'feed';

    /**
     * Whether the follower follows each of the given actors: one query,
     * returned as ["talent:7" => true, "brand:3" => false, …].
     *
     * @param  Collection<int, Actor>  $followables
     * @return array<string, bool>
     */
    public function isFollowing(?Actor $follower, Collection $followables): array
    {
        $keys = $followables
            ->map(fn (Actor $actor): string => $actor->getMorphClass().':'.$actor->getKey())
            ->unique()
            ->values();

        if ($follower === null || $keys->isEmpty()) {
            return $keys->mapWithKeys(fn (string $key): array => [$key => false])->all();
        }

        $followed = Follow::query()
            ->where('follower_type', $follower->getMorphClass())
            ->where('follower_id', $follower->getKey())
            ->where(function ($query) use ($followables): void {
                foreach ($followables->groupBy(fn (Actor $actor) => $actor->getMorphClass()) as $type => $group) {
                    $query->orWhere(fn ($q) => $q
                        ->where('followable_type', $type)
                        ->whereIn('followable_id', $group->map(fn (Actor $actor) => $actor->getKey())->all()));
                }
            })
            ->get(['followable_type', 'followable_id'])
            ->map(fn (Follow $follow): string => $follow->followable_type.':'.$follow->followable_id)
            ->flip();

        return $keys
            ->mapWithKeys(fn (string $key): array => [$key => $followed->has($key)])
            ->all();
    }

    /**
     * The inverse bulk map: whether each of the given actors follows the
     * target — one query, keyed "type:id" like isFollowing(). This is how the
     * FOLLOWING management tab resolves FOLLOWS YOU BACK without a per-row
     * lookup (the FOLLOWERS tab's mutual check is plain isFollowing()).
     *
     * @param  Collection<int, Actor>  $followers
     * @return array<string, bool>
     */
    public function followsBack(?Actor $target, Collection $followers): array
    {
        $keys = $followers
            ->map(fn (Actor $actor): string => $actor->getMorphClass().':'.$actor->getKey())
            ->unique()
            ->values();

        if ($target === null || $keys->isEmpty()) {
            return $keys->mapWithKeys(fn (string $key): array => [$key => false])->all();
        }

        $following = Follow::query()
            ->where('followable_type', $target->getMorphClass())
            ->where('followable_id', $target->getKey())
            ->where(function ($query) use ($followers): void {
                foreach ($followers->groupBy(fn (Actor $actor) => $actor->getMorphClass()) as $type => $group) {
                    $query->orWhere(fn ($q) => $q
                        ->where('follower_type', $type)
                        ->whereIn('follower_id', $group->map(fn (Actor $actor) => $actor->getKey())->all()));
                }
            })
            ->get(['follower_type', 'follower_id'])
            ->map(fn (Follow $follow): string => $follow->follower_type.':'.$follow->follower_id)
            ->flip();

        return $keys
            ->mapWithKeys(fn (string $key): array => [$key => $following->has($key)])
            ->all();
    }
}
