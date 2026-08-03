<?php

namespace App\Services;

use App\Data\Feed\FeedQueryData;
use App\Feed\Actor;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Talent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The feed ranking engine (ADR-F5, D9/D10/D14).
 *
 * The full candidate ID list is computed ONCE per feed session and cached
 * (10 min, keyed viewer + seed); pages are slices of that frozen list, so
 * pagination is provably stable — no drift, no duplicates, no omissions
 * while scrolling. New posts appear on the next session, never mid-scroll.
 *
 * Tier 1: posts by followed authors (last 30 days) in a SEEDED SHUFFLE —
 * ordered by md5(post_id . seed), deterministic given the seed. Tier 2:
 * discovery, scored by skill affinity, engagement velocity, author freshness
 * and recency decay. Interleaved (one tier-2 injection after every 4 tier-1
 * items), then capped at 2 consecutive posts per author (displaced posts
 * move down, never dropped). Every 6th position is a casting_strip slot,
 * resolved separately (DiscoverTalentsService).
 *
 * All weights live in config/feed.php — no magic numbers here.
 */
class FeedService extends Service
{
    protected string $logChannel = 'feed';

    /**
     * One feed page: hydrated posts in cached-list order, the casting-slot
     * positions inside this slice, and the opaque next cursor.
     *
     * @return array{items: Collection<int, Post>, casting_positions: list<int>, next_cursor: string|null, total: int}
     */
    public function page(FeedQueryData $query): array
    {
        $ids = $this->candidateIds($query->viewer, $query->seed, $query->skill, $query->followingOnly);

        $slice = array_slice($ids, $query->cursor, $query->perPage);
        $items = $this->hydrate($slice, $query->viewer);

        $nextOffset = $query->cursor + count($slice);

        return [
            'items' => $items,
            'casting_positions' => $this->castingPositions($query->cursor, count($slice)),
            'next_cursor' => $nextOffset < count($ids)
                ? $this->encodeCursor($query->seed, $nextOffset, $query->skill, $query->followingOnly)
                : null,
            'total' => count($ids),
        ];
    }

    /**
     * Encode a cursor: an opaque base64 {seed, offset} token (D10).
     */
    public function encodeCursor(string $seed, int $offset, ?string $skill = null, bool $followingOnly = false): string
    {
        return base64_encode(json_encode(array_filter([
            's' => $seed,
            'o' => $offset,
            'k' => $skill,
            'f' => $followingOnly ? 1 : null,
        ], fn ($value) => $value !== null)));
    }

    /**
     * Decode a cursor; malformed tokens fall back to {given seed, 0}. The
     * skill / following filters ride inside the token so a filtered stream's
     * frozen list stays consistent across pages (5P).
     *
     * @return array{seed: string, offset: int, skill: string|null, following: bool}
     */
    public function decodeCursor(?string $cursor, string $fallbackSeed): array
    {
        $fallback = ['seed' => $fallbackSeed, 'offset' => 0, 'skill' => null, 'following' => false];

        if ($cursor === null || $cursor === '') {
            return $fallback;
        }

        $decoded = json_decode((string) base64_decode($cursor, true), true);

        if (! is_array($decoded) || ! isset($decoded['s'], $decoded['o'])) {
            return $fallback;
        }

        return [
            'seed' => (string) $decoded['s'],
            'offset' => max(0, (int) $decoded['o']),
            'skill' => isset($decoded['k']) ? (string) $decoded['k'] : null,
            'following' => (bool) ($decoded['f'] ?? false),
        ];
    }

    /**
     * The frozen candidate ID list for this viewer + seed (ADR-F5), cached
     * for the session window.
     *
     * @return list<int>
     */
    public function candidateIds(?Actor $viewer, string $seed, ?string $skill = null, bool $followingOnly = false): array
    {
        $key = sprintf(
            'feed:candidates:%s:%s:%s:%s',
            $viewer !== null ? $viewer->getMorphClass().'-'.$viewer->getKey() : 'guest',
            $seed,
            $skill ?? '-',
            $followingOnly ? 'f' : 'a',
        );

        return Cache::remember(
            $key,
            now()->addMinutes((int) config('feed.ranking.cache_minutes')),
            fn (): array => $this->buildCandidateIds($viewer, $seed, $skill, $followingOnly),
        );
    }

    /**
     * Build the ranked ID list: seeded tier 1, scored tier 2, interleave,
     * diversity cap.
     *
     * @return list<int>
     */
    private function buildCandidateIds(?Actor $viewer, string $seed, ?string $skill = null, bool $followingOnly = false): array
    {
        $tier1 = $this->tierOne($viewer, $seed, $skill);
        $tier2 = $followingOnly
            ? collect()
            : $this->tierTwo($viewer, $seed, exclude: $tier1->pluck('id'), skill: $skill);

        $interleaved = $this->interleave($tier1, $tier2);
        $capped = $this->applyDiversityCap($interleaved);

        return $capped->pluck('id')->all();
    }

    /**
     * Tier 1 — followed authors, last N days, seeded shuffle (deterministic
     * given the seed; guests and zero-follow viewers get none — D14).
     *
     * @return Collection<int, object{id: int, author_key: string}>
     */
    private function tierOne(?Actor $viewer, string $seed, ?string $skill = null): Collection
    {
        if ($viewer === null) {
            return collect();
        }

        $edges = Follow::query()
            ->where('follower_type', $viewer->getMorphClass())
            ->where('follower_id', $viewer->getKey())
            ->get(['followable_type', 'followable_id']);

        if ($edges->isEmpty()) {
            return collect();
        }

        $posts = Post::query()
            ->published()
            ->when($skill !== null, fn ($query) => $query->whereHas(
                'skills', fn ($q) => $q->where('slug', $skill)
            ))
            ->where('published_at', '>=', now()->subDays((int) config('feed.ranking.tier1_days')))
            ->where(function ($query) use ($edges): void {
                foreach ($edges->groupBy('followable_type') as $type => $group) {
                    $query->orWhere(fn ($q) => $q
                        ->where('author_type', $type)
                        ->whereIn('author_id', $group->pluck('followable_id')));
                }
            })
            ->get(['id', 'author_type', 'author_id']);

        return $posts
            ->map(fn (Post $post) => (object) [
                'id' => $post->id,
                'author_key' => $post->author_type.':'.$post->author_id,
            ])
            ->sortBy(fn (object $row) => md5($row->id.$seed))
            ->values();
    }

    /**
     * Tier 2 — discovery: published posts (excluding tier 1 and the viewer's
     * own) scored by affinity + velocity + freshness + recency, jittered by
     * the seed for stable tie-breaks.
     *
     * @param  Collection<int, int>  $exclude
     * @return Collection<int, object{id: int, author_key: string}>
     */
    private function tierTwo(?Actor $viewer, string $seed, Collection $exclude, ?string $skill = null): Collection
    {
        $weights = config('feed.ranking.weights');
        $viewerSkills = $this->viewerSkillIds($viewer);
        $freshCutoff = now()->subDays((int) config('feed.ranking.fresh_author_days'));
        $halfLife = (float) config('feed.ranking.recency_half_life_days');

        $posts = Post::query()
            ->published()
            ->when($skill !== null, fn ($query) => $query->whereHas(
                'skills', fn ($q) => $q->where('slug', $skill)
            ))
            ->where('published_at', '>=', now()->subDays((int) config('feed.ranking.tier2_days')))
            ->when($exclude->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $exclude))
            ->when($viewer !== null, fn ($query) => $query->whereNot(fn ($q) => $q
                ->where('author_type', $viewer->getMorphClass())
                ->where('author_id', $viewer->getKey())))
            ->with(['skills:talent_types.id', 'author:id,created_at'])
            ->latest('published_at')
            ->limit((int) config('feed.ranking.tier2_pool'))
            ->get(['id', 'author_type', 'author_id', 'likes_count', 'comments_count', 'published_at']);

        return $posts
            ->map(function (Post $post) use ($weights, $viewerSkills, $freshCutoff, $halfLife, $seed): object {
                $ageHours = max(1.0, (float) $post->published_at->diffInHours(now()));

                $affinity = $viewerSkills === []
                    ? 0
                    : $post->skills->pluck('id')->intersect($viewerSkills)->count();

                $engagement = $post->likes_count + 2 * $post->comments_count;
                $velocity = $engagement / max($ageHours, (float) config('feed.ranking.velocity_hours'));

                $fresh = ($post->author?->created_at?->gte($freshCutoff) ?? false) ? 1.0 : 0.0;
                $recency = exp(-($ageHours / 24.0) / $halfLife * M_LN2);

                $score = $weights['affinity'] * $affinity
                    + $weights['velocity'] * log(1 + $velocity * 100)
                    + $weights['freshness'] * $fresh
                    + $weights['recency'] * $recency;

                return (object) [
                    'id' => $post->id,
                    'author_key' => $post->author_type.':'.$post->author_id,
                    'score' => $score,
                    'jitter' => md5($post->id.$seed),
                ];
            })
            ->sortBy([['score', 'desc'], ['jitter', 'asc']])
            ->values();
    }

    /**
     * Interleave: tier 1 until exhausted with one tier-2 injection after
     * every N tier-1 items (so discovery never disappears for heavy
     * followers), then the remaining tier 2.
     *
     * @param  Collection<int, object>  $tier1
     * @param  Collection<int, object>  $tier2
     * @return Collection<int, object>
     */
    private function interleave(Collection $tier1, Collection $tier2): Collection
    {
        $injectEvery = (int) config('feed.ranking.inject_every');
        $result = collect();
        $t2 = $tier2->values();
        $t2Index = 0;

        foreach ($tier1->values() as $index => $row) {
            $result->push($row);

            if (($index + 1) % $injectEvery === 0 && $t2Index < $t2->count()) {
                $result->push($t2[$t2Index++]);
            }
        }

        for (; $t2Index < $t2->count(); $t2Index++) {
            $result->push($t2[$t2Index]);
        }

        return $result;
    }

    /**
     * Author diversity cap (D9): never more than N consecutive posts from one
     * author. A displaced post moves DOWN to the next legal slot — never
     * dropped.
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    private function applyDiversityCap(Collection $rows): Collection
    {
        $max = (int) config('feed.ranking.diversity_max_consecutive');
        $pending = $rows->values()->all();
        $result = [];
        $deferred = [];

        while ($pending !== [] || $deferred !== []) {
            $placed = false;

            // Deferred posts get priority the moment they become legal.
            foreach ($deferred as $i => $row) {
                if (! $this->wouldExceedRun($result, $row->author_key, $max)) {
                    $result[] = $row;
                    unset($deferred[$i]);
                    $deferred = array_values($deferred);
                    $placed = true;
                    break;
                }
            }

            if ($placed) {
                continue;
            }

            if ($pending === []) {
                // Only illegal deferred rows remain — append them (a run at
                // the very tail beats dropping content).
                $result = array_merge($result, $deferred);
                break;
            }

            $row = array_shift($pending);

            if ($this->wouldExceedRun($result, $row->author_key, $max)) {
                $deferred[] = $row;

                continue;
            }

            $result[] = $row;
        }

        return collect($result);
    }

    /**
     * Whether appending this author would exceed the consecutive-run cap.
     *
     * @param  list<object>  $result
     */
    private function wouldExceedRun(array $result, string $authorKey, int $max): bool
    {
        $run = 0;

        for ($i = count($result) - 1; $i >= 0; $i--) {
            if ($result[$i]->author_key !== $authorKey) {
                break;
            }

            $run++;
        }

        return $run >= $max;
    }

    /**
     * The viewer's affinity Skills: a talent's own; a brand's from the
     * talents it follows (one join query); guests none.
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
     * Hydrate a slice in list order: ONE whereIn + FIELD() ordering, the full
     * eager graph, and the viewer's like state (no per-row queries).
     *
     * @param  list<int>  $ids
     * @return Collection<int, Post>
     */
    private function hydrate(array $ids, ?Actor $viewer): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $viewerLike = $viewer === null
            ? fn ($query) => $query->whereRaw('1 = 0')
            : fn ($query) => $query
                ->where('liker_type', $viewer->getMorphClass())
                ->where('liker_id', $viewer->getKey());

        $viewerBookmark = $viewer === null
            ? fn ($query) => $query->whereRaw('1 = 0')
            : fn ($query) => $query
                ->where('bookmarker_type', $viewer->getMorphClass())
                ->where('bookmarker_id', $viewer->getKey());

        return Post::query()
            ->whereIn('id', $ids)
            ->with([
                'author.media',
                'media',
                'skills',
                'project.media',
                'mentions.mentioned',
                'linkedUrls.preview',
                'viewerLike' => $viewerLike,
                'viewerBookmark' => $viewerBookmark,
            ])
            ->orderByRaw('FIELD(id, '.implode(',', array_map(intval(...), $ids)).')')
            ->get();
    }

    /**
     * Casting-strip slot positions (D9) inside this slice — every Nth global
     * feed position, translated to slice-relative indexes.
     *
     * @return list<int>
     */
    private function castingPositions(int $offset, int $count): array
    {
        $every = (int) config('feed.ranking.casting_every');
        $positions = [];

        for ($global = $offset; $global < $offset + $count; $global++) {
            if (($global + 1) % $every === 0) {
                $positions[] = $global - $offset;
            }
        }

        return $positions;
    }
}
