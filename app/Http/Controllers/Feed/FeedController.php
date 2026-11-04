<?php

namespace App\Http\Controllers\Feed;

use App\Data\Feed\FeedQueryData;
use App\Feed\Actor;
use App\Http\Resources\Feed\ActorResource;
use App\Http\Resources\Feed\PostResource;
use App\Models\Post;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\DiscoverTalentsService;
use App\Services\FeedService;
use App\Services\FollowService;
use App\Services\ProfileCompletionService;
use App\Support\FeedComposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The public feed (feed-spec §1, ADR-F5/F8): the page shell, the ranked
 * cursor-paginated item stream (FeedService) and the casting strip
 * (DiscoverTalentsService). Fully guest-readable. The Daylight shell
 * replaces the interim Blade in Prompt 5M.
 */
class FeedController extends FeedBaseController
{
    /**
     * The site root. A signed-in talent follows the login landing rule (Feed
     * once the profile is done, else Profile structure — ADR-AC). Everyone
     * else: the feed when it has something to show this viewer, otherwise
     * login (a signed-in brand goes to their home instead). Reads the same
     * cached frozen candidate list /feed slices, so "empty" here means /feed
     * would render empty too.
     */
    public function home(Request $request, FeedService $feed, ProfileCompletionService $completion): RedirectResponse
    {
        $viewer = $this->currentActor->resolve();

        if ($viewer instanceof Talent) {
            return redirect()->route($completion->homeRoute($viewer));
        }

        if ($feed->candidateIds($viewer, $this->sessionSeed($request)) !== []) {
            return redirect()->route('feed');
        }

        return $viewer !== null ? redirect()->route('dashboard') : redirect()->route('login');
    }

    /**
     * The Daylight feed shell with page 1 server-rendered from the ranked
     * stream (same frozen candidate list the Ajax pages slice — D10).
     */
    public function index(Request $request, FeedService $feed, DiscoverTalentsService $discover, FollowService $follows): View
    {
        $seed = $this->sessionSeed($request);
        $viewer = $this->currentActor->resolve();

        // Filtered modes (5P): ?skill={slug} and ?following=1 — both ride the
        // cursor afterwards so the frozen list stays consistent.
        $skill = $request->query('skill');
        $following = $request->boolean('following');

        $page = $feed->page(new FeedQueryData(
            viewer: $viewer,
            seed: $seed,
            cursor: 0,
            perPage: (int) config('feed.ranking.per_page'),
            skill: is_string($skill) && $skill !== '' ? $skill : null,
            followingOnly: $following,
        ));

        // The composer is a talent-only surface (ADR-F1): its config rides
        // along server-side, no extra round-trips.
        $composer = FeedComposer::for($viewer);

        return view('feed.index', [
            'items' => $page['items'],
            'castingPositions' => $page['casting_positions'],
            'castingTalents' => $this->castingWithState($discover, $follows, $viewer, $seed),
            'nextCursor' => $page['next_cursor'],
            'composer' => $composer,
            'activeSkill' => is_string($skill) && $skill !== '' ? $skill : null,
            'followingOnly' => $following,
            'filterSkills' => TalentType::query()->orderBy('id')->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * One ranked feed page: items in frozen-list order, casting-slot
     * positions, and the opaque next cursor (D10).
     */
    public function items(Request $request, FeedService $feed): JsonResponse
    {
        $decoded = $feed->decodeCursor($request->query('cursor'), $this->sessionSeed($request));

        // First page of a filtered stream: the filters arrive as query params;
        // afterwards they live inside the cursor (5P).
        $skill = $decoded['skill'] ?? null;
        $following = $decoded['following'] ?? false;

        if ($request->query('cursor') === null) {
            $requestSkill = $request->query('skill');
            $skill = is_string($requestSkill) && $requestSkill !== '' ? $requestSkill : null;
            $following = $request->boolean('following');
        }

        $page = $feed->page(new FeedQueryData(
            viewer: $this->currentActor->resolve(),
            seed: $decoded['seed'],
            cursor: $decoded['offset'],
            perPage: (int) config('feed.ranking.per_page'),
            skill: $skill,
            followingOnly: $following,
        ));

        $payload = [
            'items' => PostResource::collection($page['items']),
            'casting_positions' => $page['casting_positions'],
            'next_cursor' => $page['next_cursor'],
            'total' => $page['total'],
        ];

        // The web stream swaps server-rendered frames (the repo's fragment
        // convention); the API mirror stays pure JSON.
        if (! $request->is('api/*')) {
            $payload['html'] = $page['items']
                ->map(fn (Post $post): string => view('feed.partials.post', ['post' => $post])->render())
                ->values();

            if ($payload['casting_positions'] !== []) {
                $viewer = $this->currentActor->resolve();
                $talents = app(DiscoverTalentsService::class)->strip($viewer, $decoded['seed']);
                $state = app(FollowService::class)->isFollowing($viewer, $talents);
                $talents->each(fn (Talent $talent) => $talent->setAttribute(
                    'followed_by_viewer',
                    $state['talent:'.$talent->id] ?? false,
                ));

                $payload['casting_html'] = view('feed.partials.casting', ['talents' => $talents])->render();
            }
        }

        return response()->success($payload);
    }

    /**
     * A casting strip with the viewer's follow-state stamped on each talent.
     */
    private function castingWithState(DiscoverTalentsService $discover, FollowService $follows, ?Actor $viewer, string $seed): Collection
    {
        $talents = $discover->strip($viewer, $seed);
        $state = $follows->isFollowing($viewer, $talents);

        return $talents->each(fn (Talent $talent) => $talent->setAttribute(
            'followed_by_viewer',
            $state['talent:'.$talent->id] ?? false,
        ));
    }

    /**
     * One casting strip: discovery talents with the viewer's follow-state,
     * never repeating within the feed session.
     */
    public function casting(Request $request, DiscoverTalentsService $discover, FollowService $follows): JsonResponse
    {
        $seed = $request->query('seed', $this->sessionSeed($request));
        $viewer = $this->currentActor->resolve();

        $talents = $discover->strip($viewer, (string) $seed);
        $state = $follows->isFollowing($viewer, $talents);

        return response()->success([
            'talents' => $talents->map(fn (Talent $talent): array => [
                ...(new ActorResource($talent))->resolve(),
                'headline' => $talent->headline,
                'skills' => $talent->talentTypes->map(fn ($skill) => [
                    'name' => $skill->name, 'slug' => $skill->slug, 'icon' => $skill->icon,
                ])->values(),
                'followers_count' => $talent->followers_count,
                'followed_by_viewer' => $state['talent:'.$talent->id] ?? false,
            ])->values(),
        ]);
    }

    /**
     * The per-visitor feed-session seed (D14): derived from the session ID —
     * two visitors differ, one visitor's refresh is stable. The stateless API
     * derives from the actor (or client fingerprint) + hour instead; mobile
     * clients keep their seed by passing the cursor back.
     */
    private function sessionSeed(Request $request): string
    {
        $basis = $request->hasSession()
            ? $request->session()->getId()
            : ($this->currentActor->resolve()?->getKey() ?? $request->ip().'|'.$request->userAgent()).'|'.now()->format('YmdH');

        return substr(sha1('feed-seed:'.$basis), 0, 16);
    }
}
