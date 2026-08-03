<?php

namespace App\Services;

use App\Feed\Actor;
use App\Models\Follow;
use App\Models\Talent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The casting strip (D9): talents the viewer does NOT follow, ranked by skill
 * overlap, recent activity and profile completeness — 12 per strip, never
 * repeating within one feed session (shown IDs tracked per viewer + seed).
 * Strip contents cache for 5 minutes.
 */
class DiscoverTalentsService extends Service
{
    protected string $logChannel = 'feed';

    /**
     * One strip of talents for the viewer's session. Each call marks its
     * talents as shown, so the next slot surfaces fresh faces.
     *
     * @return Collection<int, Talent>
     */
    public function strip(?Actor $viewer, string $seed): Collection
    {
        $size = (int) config('feed.ranking.casting_size');
        $shownKey = $this->shownKey($viewer, $seed);
        $shown = Cache::get($shownKey, []);

        $candidates = $this->candidates($viewer)
            ->reject(fn (Talent $talent): bool => in_array($talent->id, $shown, true))
            ->take($size)
            ->values();

        Cache::put(
            $shownKey,
            array_merge($shown, $candidates->pluck('id')->all()),
            now()->addMinutes((int) config('feed.ranking.cache_minutes')),
        );

        return $candidates;
    }

    /**
     * The ranked candidate pool for this viewer. Only the ranked ID LIST is
     * cached (5 minutes) — never model instances (serialized Eloquent objects
     * break across deploys/opcache states); rows rehydrate per call in ranked
     * order.
     *
     * @return Collection<int, Talent>
     */
    private function candidates(?Actor $viewer): Collection
    {
        $key = sprintf(
            'feed:casting:%s',
            $viewer !== null ? $viewer->getMorphClass().'-'.$viewer->getKey() : 'guest',
        );

        /** @var list<int> $ids */
        $ids = Cache::remember(
            $key,
            now()->addMinutes((int) config('feed.ranking.casting_cache_minutes')),
            fn (): array => $this->rankCandidates($viewer)->pluck('id')->all(),
        );

        if ($ids === []) {
            return collect();
        }

        return Talent::query()
            ->whereIn('id', $ids)
            ->with(['talentTypes', 'media'])
            ->orderByRaw('FIELD(id, '.implode(',', array_map(intval(...), $ids)).')')
            ->get();
    }

    /**
     * Rank published talents the viewer doesn't follow: skill overlap +
     * recent posting activity + profile completeness (avatar, bio, work).
     *
     * @return Collection<int, Talent>
     */
    private function rankCandidates(?Actor $viewer): Collection
    {
        $viewerSkills = $this->viewerSkillIds($viewer);
        $followedIds = $this->followedTalentIds($viewer);

        return Talent::query()
            ->where('is_published', true)
            ->whereNotIn('id', $followedIds)
            ->when($viewer instanceof Talent, fn ($query) => $query->whereKeyNot($viewer->getKey()))
            ->with(['talentTypes', 'media'])
            ->orderByDesc('view_count')
            ->limit(120)
            ->get()
            ->map(function (Talent $talent) use ($viewerSkills): Talent {
                $overlap = $viewerSkills === []
                    ? 0
                    : $talent->talentTypes->pluck('id')->intersect($viewerSkills)->count();

                $activity = min(1.0, $talent->posts_count / 5);
                $completeness = ($talent->avatar_url !== null ? 0.5 : 0)
                    + (filled($talent->bio) ? 0.25 : 0)
                    + ($talent->posts_count > 0 ? 0.25 : 0);

                $talent->setAttribute('casting_score', $overlap * 2 + $activity + $completeness);

                return $talent;
            })
            ->sortByDesc('casting_score')
            ->values();
    }

    /**
     * The viewer's affinity Skills (same definition as FeedService).
     *
     * @return list<int>
     */
    private function viewerSkillIds(?Actor $viewer): array
    {
        if ($viewer instanceof Talent) {
            return $viewer->talentTypes()->pluck('talent_types.id')->all();
        }

        if ($viewer === null) {
            return [];
        }

        return Follow::query()
            ->where('follower_type', $viewer->getMorphClass())
            ->where('follower_id', $viewer->getKey())
            ->where('followable_type', 'talent')
            ->join('talent_talent_type', 'talent_talent_type.talent_id', '=', 'follows.followable_id')
            ->distinct()
            ->pluck('talent_talent_type.talent_type_id')
            ->all();
    }

    /**
     * IDs of talents the viewer already follows (excluded from discovery).
     *
     * @return list<int>
     */
    private function followedTalentIds(?Actor $viewer): array
    {
        if ($viewer === null) {
            return [];
        }

        return Follow::query()
            ->where('follower_type', $viewer->getMorphClass())
            ->where('follower_id', $viewer->getKey())
            ->where('followable_type', 'talent')
            ->pluck('followable_id')
            ->all();
    }

    /**
     * The shown-IDs session key (no repeats within a feed session).
     */
    private function shownKey(?Actor $viewer, string $seed): string
    {
        return sprintf(
            'feed:casting-shown:%s:%s',
            $viewer !== null ? $viewer->getMorphClass().'-'.$viewer->getKey() : 'guest',
            $seed,
        );
    }
}
