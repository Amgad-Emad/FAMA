<?php

namespace App\Http\Controllers\Feed;

use App\Feed\Actor;
use App\Http\Resources\Feed\ActorResource;
use App\Models\Follow;
use App\Models\Talent;
use App\Services\FollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * A talent's audience (feed-spec §2): GET /talents/{username}/followers and
 * /following, paginated 24, each row an actor identity plus the CURRENT
 * actor's follow-state — resolved in ONE bulk FollowService query, never
 * per row.
 */
class FollowListController extends FeedBaseController
{
    /**
     * Who follows this talent (talents and brands).
     */
    public function followers(string $username, FollowService $follows): JsonResponse
    {
        $talent = $this->findTalent($username);

        $page = $talent->followers()
            ->with('follower.media')
            ->latest('created_at')
            ->paginate(24);

        return $this->respond($page, $follows, side: 'follower');
    }

    /**
     * Who this talent follows.
     */
    public function following(string $username, FollowService $follows): JsonResponse
    {
        $talent = $this->findTalent($username);

        $page = $talent->following()
            ->with('followable.media')
            ->latest('created_at')
            ->paginate(24);

        return $this->respond($page, $follows, side: 'followable');
    }

    /**
     * Shape a follows page into actor rows + the viewer's follow-state map.
     *
     * @param  LengthAwarePaginator<int, Follow>  $page
     */
    private function respond(LengthAwarePaginator $page, FollowService $follows, string $side): JsonResponse
    {
        $actors = $page->getCollection()
            ->map(fn (Follow $follow) => $follow->getRelation($side))
            ->filter(fn ($actor) => $actor instanceof Actor)
            ->values();

        $state = $follows->isFollowing($this->currentActor->resolve(), $actors);

        $rows = $actors->map(fn (Actor $actor): array => [
            ...(new ActorResource($actor))->resolve(),
            'followed_by_viewer' => $state[$actor->getMorphClass().':'.$actor->getKey()] ?? false,
        ]);

        return response()->paginated($page, $rows);
    }

    /**
     * Resolve the published talent by Username.
     */
    private function findTalent(string $username): Talent
    {
        $talent = Talent::query()->where('slug', $username)->where('is_published', true)->first();

        abort_if($talent === null, 404);

        return $talent;
    }
}
