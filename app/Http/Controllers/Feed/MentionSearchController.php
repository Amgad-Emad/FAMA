<?php

namespace App\Http\Controllers\Feed;

use App\Feed\Actor;
use App\Models\Brand;
use App\Models\Talent;
use App\Services\FollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Composer @mention autocomplete (feed-spec §7, revised 5U): matches talents
 * and brands on Username AND display name, EXCLUDING the current actor;
 * ranked people-you-follow first, then prefix matches, then substring, then
 * Username. 8 results total, grouped talents-first, each with a TALENT/BRAND
 * type badge client-side. Fixed query count regardless of result size
 * (candidates ×2 + one bulk follow map; avatars eager-loaded).
 */
class MentionSearchController extends FeedBaseController
{
    private const LIMIT = 8;

    private const POOL = 12; // over-fetch per type, rank, then slice

    /**
     * Search actors by Username / display-name prefix or fragment.
     */
    public function __invoke(Request $request, FollowService $follows): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 1) {
            return response()->success(['talents' => [], 'brands' => []]);
        }

        $viewer = $this->currentActor->resolve();
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

        $talents = Talent::query()
            ->where('is_published', true)
            ->when($viewer instanceof Talent, fn ($query) => $query->whereKeyNot($viewer->getKey()))
            ->where(fn ($query) => $query->where('slug', 'like', $like)->orWhere('display_name', 'like', $like))
            ->with('media')
            ->orderBy('slug')
            ->limit(self::POOL)
            ->get();

        $brands = Brand::query()
            ->where('is_published', true)
            ->when($viewer instanceof Brand, fn ($query) => $query->whereKeyNot($viewer->getKey()))
            ->where(fn ($query) => $query->where('slug', 'like', $like)->orWhere('name', 'like', $like))
            ->with('media')
            ->orderBy('slug')
            ->limit(self::POOL)
            ->get();

        // One bulk follow map ranks the people the viewer follows above all.
        $followed = $follows->isFollowing($viewer, $talents->concat($brands)->values());

        $ranked = fn (Collection $actors) => $actors
            ->sortBy([
                fn (Actor $a, Actor $b) => $this->score($b, $q, $followed) <=> $this->score($a, $q, $followed),
                fn (Actor $a, Actor $b) => strcmp($a->feedUsername(), $b->feedUsername()),
            ])
            ->values();

        $talents = $ranked($talents)->take(self::LIMIT);
        $brands = $ranked($brands)->take(max(0, self::LIMIT - $talents->count()));

        $present = fn (Actor $actor): array => [
            'type' => $actor->getMorphClass(),
            'id' => $actor->getKey(),
            'username' => $actor->feedUsername(),
            'display_name' => $actor->feedDisplayName(),
            'avatar_url' => $actor->feedAvatarUrl(),
        ];

        return response()->success([
            'talents' => $talents->map($present)->values(),
            'brands' => $brands->map($present)->values(),
        ]);
    }

    /**
     * Rank: followed (4) > Username/display-name prefix (2) > substring (0).
     *
     * @param  array<string, bool>  $followed
     */
    private function score(Actor $actor, string $q, array $followed): int
    {
        $score = 0;
        $needle = mb_strtolower($q);

        if ($followed[$actor->getMorphClass().':'.$actor->getKey()] ?? false) {
            $score += 4;
        }

        if (str_starts_with(mb_strtolower($actor->feedUsername()), $needle)
            || str_starts_with(mb_strtolower($actor->feedDisplayName()), $needle)) {
            $score += 2;
        }

        return $score;
    }
}
