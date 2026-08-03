<?php

namespace App\Services;

use App\Data\Feed\SuggestionData;
use App\Feed\Actor;
use App\Models\Brand;
use App\Models\Contract;
use App\Models\Follow;
use App\Models\SuggestionDismissal;
use App\Models\Talent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The follow-suggestion engine behind the feed rails (revision 5Z).
 *
 * Ranking is tiered (config feed.suggestions.tiers — bonuses dominate the
 * high-signal base score so tier order holds; the base breaks ties). Every
 * candidate carries a TRUTHFUL reason as a {key, params} pair translated at
 * serve time in the viewer's locale — a candidate with no reason renders
 * without the line, never with a fabricated one.
 *
 * Exclusions (all mandatory): the viewer, already-followed, dismissed,
 * unpublished/suspended, and anything shown in the last 7 days without being
 * acted on (tracked in cache, not a table). The ranked {id, reason} list is
 * cached 5 minutes per actor (plain arrays only — never models); models
 * rehydrate per page slice. Keys are documented in docs/performance.md;
 * invalidation: follow, dismiss, own-profile update.
 */
class SuggestionService extends Service
{
    protected string $logChannel = 'feed';

    /**
     * Talent suggestions for the viewer.
     *
     * @return array{items: list<array<string, mixed>>, next_offset: int|null}
     */
    public function talentsFor(?Actor $viewer, int $limit = 5, int $offset = 0, string $seed = ''): array
    {
        $ranked = $this->remember($viewer, 'talents', $seed, fn (): array => $this->rankTalents($viewer, $seed));

        return $this->slice($ranked, 'talent', $limit, $offset);
    }

    /**
     * Brand suggestions for the viewer. Brands are never suggested to brands
     * — the rail hides on an empty set.
     *
     * @return array{items: list<array<string, mixed>>, next_offset: int|null}
     */
    public function brandsFor(?Actor $viewer, int $limit = 5, int $offset = 0, string $seed = ''): array
    {
        if ($viewer instanceof Brand) {
            return ['items' => [], 'next_offset' => null];
        }

        $ranked = $this->remember($viewer, 'brands', $seed, fn (): array => $this->rankBrands($viewer, $seed));

        return $this->slice($ranked, 'brand', $limit, $offset);
    }

    /**
     * Permanently dismiss a suggestion for this actor.
     */
    public function dismiss(Actor $actor, string $type, int $id): void
    {
        $this->runInTransaction(function () use ($actor, $type, $id): void {
            SuggestionDismissal::query()->firstOrCreate([
                'actor_type' => $actor->getMorphClass(),
                'actor_id' => $actor->getKey(),
                'suggestible_type' => $type,
                'suggestible_id' => $id,
            ], ['dismissed_at' => now()]);
        }, ['actor' => $actor->getMorphClass().':'.$actor->getKey(), 'dismiss' => "{$type}:{$id}"]);

        $this->forget($actor);
    }

    /**
     * Mark served suggestions as shown (the 7-day unacted rest, cache-only).
     *
     * @param  list<int>  $ids
     */
    public function markShown(?Actor $viewer, string $type, array $ids): void
    {
        $key = $this->shownKey($viewer, $type);
        $shown = Cache::get($key, []);
        $now = now()->timestamp;

        foreach ($ids as $id) {
            $shown[$id] = $now;
        }

        Cache::put($key, $shown, now()->addDays((int) config('feed.suggestions.shown_ttl_days')));
    }

    /**
     * Drop the actor's cached suggestion lists (follow / dismiss / own
     * profile update).
     */
    public function forget(?Actor $actor): void
    {
        foreach (['talents', 'brands'] as $type) {
            Cache::forget($this->cacheKey($actor, $type, ''));
        }
    }

    // ----- ranking: talents --------------------------------------------------

    /**
     * @return list<array{id: int, score: float, reason: array{key: string, params: array<string, mixed>}|null}>
     */
    private function rankTalents(?Actor $viewer, string $seed): array
    {
        $excluded = $this->excludedIds($viewer, 'talent');
        $shown = $this->shownIds($viewer, 'talent');
        $pool = Talent::query()
            ->where('is_published', true)
            ->where('is_active', true)
            ->whereNotIn('id', $excluded)
            ->when($viewer instanceof Talent, fn ($query) => $query->whereKeyNot($viewer->getKey()))
            ->get(['id', 'slug', 'display_name', 'base_city', 'followers_count', 'posts_count', 'created_at']);

        if ($pool->isEmpty()) {
            return [];
        }

        $tiers = config('feed.suggestions.tiers');
        $activeWithin7 = $this->activeWithinDays('talent', $pool->pluck('id'), 7);

        // Viewer-specific tier signals (each ONE query).
        $secondDegree = collect();
        $mentionedInLiked = collect();
        $viewerSkillNames = collect();
        $sharedSkillIds = collect();
        $searchedSkillIds = collect();
        $contractPartners = collect();
        $city = null;

        if ($viewer instanceof Talent) {
            $city = $viewer->base_city;
            $followedTalentIds = $this->followedIds($viewer, 'talent');

            if ($followedTalentIds !== []) {
                $secondDegree = Follow::query()
                    ->whereIn('follower_id', $followedTalentIds)
                    ->where('follower_type', 'talent')
                    ->where('followable_type', 'talent')
                    ->groupBy('followable_id')
                    ->select('followable_id', DB::raw('COUNT(*) as cnt'), DB::raw('MIN(follower_id) as sample_id'))
                    ->pluck('cnt', 'followable_id');
            }

            $mentionedInLiked = DB::table('mentions')
                ->where('mentioned_type', 'talent')
                ->where('mentionable_type', 'post')
                ->whereIn('mentionable_id', fn ($query) => $query
                    ->select('likeable_id')->from('likes')
                    ->where('liker_type', 'talent')->where('liker_id', $viewer->getKey())
                    ->where('likeable_type', 'post'))
                ->distinct()
                ->pluck('mentioned_id');

            $viewerSkills = $viewer->talentTypes()->get(['talent_types.id', 'name']);
            $viewerSkillNames = $viewerSkills->pluck('name', 'id');
            $sharedSkillIds = DB::table('talent_talent_type')
                ->whereIn('talent_type_id', $viewerSkills->pluck('id'))
                ->distinct()
                ->pluck('talent_id');
        }

        if ($viewer instanceof Brand) {
            $city = $viewer->base_city;

            // "Skills the brand recently filtered or searched on" — the
            // strongest recorded signal is the skills of the talents the brand
            // follows plus its Projects' sought roles (documented proxy).
            $searchedSkillIds = DB::table('talent_talent_type')
                ->whereIn('talent_type_id', fn ($query) => $query
                    ->select('talent_type_id')->from('brand_projects')
                    ->where('brand_id', $viewer->getKey())
                    ->whereNotNull('talent_type_id'))
                ->distinct()
                ->pluck('talent_id');

            $contractPartners = Contract::query()
                ->where('brand_id', $viewer->getKey())
                ->groupBy('talent_id')
                ->select('talent_id', DB::raw('MIN(title) as title'))
                ->pluck('title', 'talent_id');
        }

        return $this->scoreAndSort($pool, $seed, function ($row) use (
            $tiers, $viewer, $secondDegree, $mentionedInLiked, $sharedSkillIds,
            $searchedSkillIds, $contractPartners, $city, $viewerSkillNames, $activeWithin7
        ): array {
            $bonus = 0.0;
            $reason = null;

            if (($count = $secondDegree->get($row->id)) !== null) {
                $bonus = $tiers['second_degree'];
                $reason = ['key' => 'followed_by', 'params' => ['count' => (int) $count, 'id' => $row->id]];
            } elseif ($mentionedInLiked->contains($row->id)) {
                $bonus = $tiers['mentioned_in_liked'];
            } elseif (($title = $contractPartners->get($row->id)) !== null) {
                $bonus = $tiers['contract_history'];
                $reason = ['key' => 'worked_together', 'params' => ['title' => (string) $title]];
            } elseif ($sharedSkillIds->contains($row->id) || $searchedSkillIds->contains($row->id)) {
                $bonus = $tiers['shared_skill'];

                if ($viewer instanceof Talent && $viewerSkillNames->isNotEmpty()) {
                    $reason = ['key' => 'shared_skill', 'params' => ['skill' => $viewerSkillNames->first()]];
                }
            } elseif ($city !== null && $row->base_city === $city) {
                $bonus = $tiers['same_city'];

                if ($row->created_at?->gt(now()->subDays((int) config('feed.ranking.fresh_author_days')))) {
                    $reason = ['key' => 'new_in_city', 'params' => ['city' => $city]];
                }
            }

            if ($reason === null && $activeWithin7->contains($row->id)) {
                $reason = ['key' => 'active_week', 'params' => []];
            }

            return [$bonus, $reason];
        }, 'talent', $shown);
    }

    // ----- ranking: brands ---------------------------------------------------

    /**
     * @return list<array{id: int, score: float, reason: array{key: string, params: array<string, mixed>}|null}>
     */
    private function rankBrands(?Actor $viewer, string $seed): array
    {
        $excluded = $this->excludedIds($viewer, 'brand');
        $shown = $this->shownIds($viewer, 'brand');
        $pool = Brand::query()
            ->where('is_published', true)
            ->where('is_active', true)
            ->whereNotIn('id', $excluded)
            ->get(['id', 'slug', 'name', 'base_city', 'industry', 'is_verified', 'followers_count', 'created_at']);

        if ($pool->isEmpty()) {
            return [];
        }

        $tiers = config('feed.suggestions.tiers');
        $skillOverlap = collect();
        $contractPartners = collect();
        $networkFollowed = collect();
        $city = null;

        if ($viewer instanceof Talent) {
            $city = $viewer->base_city;

            // Brands whose sought roles overlap the viewer's Skills.
            $skillOverlap = DB::table('brand_projects')
                ->whereIn('talent_type_id', fn ($query) => $query
                    ->select('talent_type_id')->from('talent_talent_type')
                    ->where('talent_id', $viewer->getKey()))
                ->distinct()
                ->pluck('brand_id');

            $contractPartners = Contract::query()
                ->where('talent_id', $viewer->getKey())
                ->groupBy('brand_id')
                ->select('brand_id', DB::raw('MIN(title) as title'))
                ->pluck('title', 'brand_id');

            $followedTalentIds = $this->followedIds($viewer, 'talent');

            if ($followedTalentIds !== []) {
                $networkFollowed = Follow::query()
                    ->whereIn('follower_id', $followedTalentIds)
                    ->where('follower_type', 'talent')
                    ->where('followable_type', 'brand')
                    ->groupBy('followable_id')
                    ->select('followable_id', DB::raw('COUNT(*) as cnt'))
                    ->pluck('cnt', 'followable_id');
            }
        }

        $activeWithin7 = collect(); // brands do not post; "active" = recent contracts
        $recentBrandActivity = Contract::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->whereIn('brand_id', $pool->pluck('id'))
            ->distinct()
            ->pluck('brand_id');

        return $this->scoreAndSort($pool, $seed, function ($row) use (
            $tiers, $skillOverlap, $contractPartners, $networkFollowed, $city, $recentBrandActivity
        ): array {
            $bonus = 0.0;
            $reason = null;

            if ($skillOverlap->contains($row->id)) {
                $bonus = $tiers['shared_skill'];
            }

            if (($title = $contractPartners->get($row->id)) !== null) {
                $bonus = max($bonus, $tiers['contract_history']);
                $reason = ['key' => 'worked_together', 'params' => ['title' => (string) $title]];
            } elseif (($count = $networkFollowed->get($row->id)) !== null) {
                $bonus = max($bonus, $tiers['network_followed']);
                $reason = ['key' => 'followed_by', 'params' => ['count' => (int) $count, 'id' => $row->id]];
            } elseif ($city !== null && $row->base_city === $city) {
                $bonus = max($bonus, $tiers['same_city']);

                if ($row->created_at?->gt(now()->subDays((int) config('feed.ranking.fresh_author_days')))) {
                    $reason = ['key' => 'new_in_city', 'params' => ['city' => $city]];
                }
            }

            if ($reason === null && $recentBrandActivity->contains($row->id)) {
                $reason = ['key' => 'active_week', 'params' => []];
            }

            return [$bonus, $reason];
        }, 'brand', $shown);
    }

    // ----- shared machinery --------------------------------------------------

    /**
     * Score a pool (tier bonus + high-signal base + seeded jitter) and return
     * the ranked plain-array list.
     */
    private function scoreAndSort(Collection $pool, string $seed, callable $tierResolver, string $authorType = 'talent', array $deprioritize = []): array
    {
        $signal = config('feed.suggestions.signal');
        $penalty = (float) config('feed.suggestions.shown_penalty');
        $shown = array_flip($deprioritize);
        $recentPosts = $this->recentPostCounts($authorType, $pool->pluck('id'));

        return $pool
            ->map(function ($row) use ($signal, $penalty, $shown, $recentPosts, $seed, $tierResolver): array {
                [$bonus, $reason] = $tierResolver($row);

                $completeness = (isset($row->display_name) ? 1 : 0)
                    + (($row->is_verified ?? false) ? 1 : 0);
                $base = $signal['completeness'] * $completeness
                    + $signal['recent_posts'] * min(5, (int) $recentPosts->get($row->id, 0))
                    + $signal['followers'] * log(1 + (int) $row->followers_count)
                    + $signal['age_days'] * log(1 + max(0, now()->diffInDays($row->created_at ?? now())));

                // Shown-but-unacted rests below every fresh candidate.
                $rest = isset($shown[(int) $row->id]) ? $penalty : 0.0;

                return [
                    'id' => (int) $row->id,
                    'score' => $bonus + $base - $rest,
                    'jitter' => md5($row->id.$seed),
                    'reason' => $reason,
                ];
            })
            ->sortBy([['score', 'desc'], ['jitter', 'asc']])
            ->map(fn (array $row): array => ['id' => $row['id'], 'score' => $row['score'], 'reason' => $row['reason']])
            ->values()
            ->all();
    }

    /**
     * Slice the ranked list, hydrate the page's models (avatars + Skills
     * eager), attach translated reasons, and mark the page as shown.
     *
     * @param  list<array{id: int, reason: array{key: string, params: array<string, mixed>}|null}>  $ranked
     * @return array{items: list<array<string, mixed>>, next_offset: int|null}
     */
    private function slice(array $ranked, string $type, int $limit, int $offset): array
    {
        $page = array_slice($ranked, $offset, $limit);

        if ($page === []) {
            return ['items' => [], 'next_offset' => null];
        }

        $ids = array_column($page, 'id');
        $order = 'FIELD(id, '.implode(',', array_map(intval(...), $ids)).')';

        $models = $type === 'talent'
            ? Talent::query()->whereIn('id', $ids)->with(['talentTypes', 'media'])->orderByRaw($order)->get()->keyBy('id')
            : Brand::query()->whereIn('id', $ids)->with(['media', 'projects:id,brand_id,talent_type_id'])->orderByRaw($order)->get()->keyBy('id');

        $skillNames = $type === 'brand' ? $this->brandSkillNames($models) : collect();

        // One batched lookup for every followed_by sample handle on this page
        // — the query count stays fixed regardless of limit.
        $followedByIds = collect($page)
            ->filter(fn (array $row) => ($row['reason']['key'] ?? null) === 'followed_by')
            ->pluck('id');
        $sampleHandles = $followedByIds->isEmpty() ? collect() : Follow::query()
            ->where('followable_type', $type)
            ->whereIn('followable_id', $followedByIds)
            ->where('follower_type', 'talent')
            ->join('talents', 'talents.id', '=', 'follows.follower_id')
            ->groupBy('follows.followable_id')
            ->select('follows.followable_id', DB::raw('MIN(talents.slug) as slug'))
            ->pluck('slug', 'follows.followable_id');

        $items = [];

        foreach ($page as $row) {
            $entity = $models->get($row['id']);

            if ($entity === null) {
                continue;
            }

            $skills = $type === 'talent'
                ? $entity->talentTypes->take(2)->map(fn ($skill) => ['name' => $skill->name, 'slug' => $skill->slug])->values()->all()
                : collect($skillNames->get($row['id'], []))->take(2)->values()->all();

            $items[] = (new SuggestionData(
                entity: $entity,
                skills: $skills,
                followersCount: (int) $entity->followers_count,
                reason: $this->translateReason($row['reason'], $entity, $sampleHandles),
            ))->toPayload();
        }

        $nextOffset = $offset + count($page);

        return [
            'items' => $items,
            'next_offset' => $nextOffset < count($ranked) ? $nextOffset : null,
        ];
    }

    /**
     * Turn a cached {key, params} reason into the viewer-locale line. The
     * second-degree reason resolves a sample follower handle here (one query
     * per page, only when that reason is present).
     */
    private function translateReason(?array $reason, Actor $entity, Collection $sampleHandles): ?string
    {
        if ($reason === null) {
            return null;
        }

        return match ($reason['key']) {
            'followed_by' => $this->followedByLine($entity, (int) $reason['params']['count'], $sampleHandles),
            'shared_skill' => __('Works in :skill, like you', ['skill' => $this->skillLabel($reason['params']['skill'] ?? '')]),
            'worked_together' => __('You worked together on :title', ['title' => $this->skillLabel($reason['params']['title'] ?? '')]),
            'new_in_city' => __('New in :city', ['city' => $reason['params']['city'] ?? '']),
            'active_week' => __('Active this week'),
            default => null,
        };
    }

    /**
     * "Followed by @handle and N others" — the sample handle comes from the
     * page's single batched lookup.
     */
    private function followedByLine(Actor $entity, int $count, Collection $sampleHandles): string
    {
        $sample = $sampleHandles->get($entity->getKey());

        if ($sample === null) {
            return __('Active this week');
        }

        return $count > 1
            ? __('Followed by @:handle and :count others', ['handle' => $sample, 'count' => $count - 1])
            : __('Followed by @:handle', ['handle' => $sample]);
    }

    /**
     * Translatable-attribute values arrive as arrays — pick the locale.
     */
    private function skillLabel(mixed $value): string
    {
        if (is_array($value)) {
            return (string) ($value[app()->getLocale()] ?? $value['en'] ?? reset($value));
        }

        return (string) $value;
    }

    /**
     * Every mandatory exclusion (5Z): followed + dismissed + shown-unacted.
     * The viewer and unpublished/suspended rows are excluded in the pool
     * query itself.
     *
     * @return list<int>
     */
    private function excludedIds(?Actor $viewer, string $type): array
    {
        if ($viewer === null) {
            return [];
        }

        $followed = Follow::query()
            ->where('follower_type', $viewer->getMorphClass())
            ->where('follower_id', $viewer->getKey())
            ->where('followable_type', $type)
            ->pluck('followable_id')
            ->all();

        $dismissed = SuggestionDismissal::query()
            ->where('actor_type', $viewer->getMorphClass())
            ->where('actor_id', $viewer->getKey())
            ->where('suggestible_type', $type)
            ->pluck('suggestible_id')
            ->all();

        // HARD exclusions only: never suggest someone the viewer already
        // follows or has dismissed. The shown-rest is handled separately as a
        // soft deprioritization (shownIds) so a small pool never empties.
        return array_values(array_unique([...$followed, ...$dismissed]));
    }

    /**
     * The shown-but-unacted set inside its TTL — deprioritized in ranking (not
     * removed), so it rests below fresh candidates yet still fills the rail
     * when nothing fresher is left.
     *
     * @return list<int>
     */
    private function shownIds(?Actor $viewer, string $type): array
    {
        if ($viewer === null) {
            return [];
        }

        $cutoff = now()->subDays((int) config('feed.suggestions.shown_ttl_days'))->timestamp;

        return array_map('intval', array_keys(array_filter(
            Cache::get($this->shownKey($viewer, $type === 'talent' ? 'talents' : 'brands'), []),
            fn (int $at): bool => $at >= $cutoff,
        )));
    }

    /**
     * @return list<int>
     */
    private function followedIds(Actor $viewer, string $type): array
    {
        return Follow::query()
            ->where('follower_type', $viewer->getMorphClass())
            ->where('follower_id', $viewer->getKey())
            ->where('followable_type', $type)
            ->pluck('followable_id')
            ->all();
    }

    /**
     * Published posts in the last 30 days per author (one aggregate query).
     */
    private function recentPostCounts(string $authorType, Collection $ids): Collection
    {
        if ($authorType !== 'talent' || $ids->isEmpty()) {
            return collect();
        }

        return DB::table('posts')
            ->where('author_type', 'talent')
            ->whereIn('author_id', $ids)
            ->where('state', 'published')
            ->whereNull('deleted_at')
            ->where('published_at', '>=', now()->subDays(30))
            ->groupBy('author_id')
            ->select('author_id', DB::raw('COUNT(*) as cnt'))
            ->pluck('cnt', 'author_id');
    }

    /**
     * Authors who published within N days (the "Active this week" signal).
     */
    private function activeWithinDays(string $authorType, Collection $ids, int $days): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return DB::table('posts')
            ->where('author_type', $authorType)
            ->whereIn('author_id', $ids)
            ->where('state', 'published')
            ->whereNull('deleted_at')
            ->where('published_at', '>=', now()->subDays($days))
            ->distinct()
            ->pluck('author_id');
    }

    /**
     * The Skills a brand's Projects seek, keyed by brand id (for the DTO's
     * top-two chips).
     */
    private function brandSkillNames(Collection $brands): Collection
    {
        $typeIds = $brands->flatMap(fn (Brand $brand) => $brand->projects->pluck('talent_type_id'))->filter()->unique();

        if ($typeIds->isEmpty()) {
            return collect();
        }

        $types = \App\Models\TalentType::query()->whereIn('id', $typeIds)->get(['id', 'name', 'slug'])->keyBy('id');

        return $brands->mapWithKeys(function (Brand $brand) use ($types) {
            $skills = $brand->projects->pluck('talent_type_id')->filter()->unique()
                ->map(fn ($id) => $types->get($id))
                ->filter()
                ->map(fn ($type) => ['name' => $type->name, 'slug' => $type->slug])
                ->values()
                ->all();

            return [$brand->id => $skills];
        });
    }

    /**
     * Cache the ranked list (plain arrays) for 5 minutes per actor+type.
     */
    private function remember(?Actor $viewer, string $type, string $seed, callable $build): array
    {
        return Cache::remember(
            $this->cacheKey($viewer, $type, $viewer === null ? $seed : ''),
            now()->addMinutes((int) config('feed.suggestions.cache_minutes')),
            $build,
        );
    }

    /**
     * feed:suggestions:{type}:{morph-id|guest:seed} — docs/performance.md.
     */
    private function cacheKey(?Actor $viewer, string $type, string $seed): string
    {
        $who = $viewer !== null
            ? $viewer->getMorphClass().'-'.$viewer->getKey()
            : 'guest:'.$seed;

        return "feed:suggestions:{$type}:{$who}";
    }

    private function shownKey(?Actor $viewer, string $type): string
    {
        $who = $viewer !== null ? $viewer->getMorphClass().'-'.$viewer->getKey() : 'guest';

        return "feed:suggestions-shown:{$type}:{$who}";
    }
}
