<?php

namespace App\Http\Controllers\Feed;

use App\Actions\Feed\ToggleFollow;
use App\Feed\Actor;
use App\Models\Brand;
use App\Models\Talent;
use Illuminate\Http\JsonResponse;

/**
 * Follow edges (feed-spec §2): POST follows, DELETE unfollows. The morph-
 * aliased {type} names the followable side; the action enforces the allowed
 * edges and the policy denies self-follow.
 */
class FollowController extends FeedBaseController
{
    /**
     * Follow the {type}/{id} party as the current actor.
     */
    public function store(string $type, int $id, ToggleFollow $toggleFollow): JsonResponse
    {
        $followable = $this->resolveFollowable($type, $id);

        $this->gate()->authorize('create', [\App\Models\Follow::class, $followable]);

        $result = $toggleFollow($this->currentActor->resolve(), $followable, onlyFollow: true);

        return response()->success($result);
    }

    /**
     * Withdraw the actor's follow of the {type}/{id} party.
     */
    public function destroy(string $type, int $id, ToggleFollow $toggleFollow): JsonResponse
    {
        $followable = $this->resolveFollowable($type, $id);

        $this->gate()->authorize('create', [\App\Models\Follow::class, $followable]);

        $result = $toggleFollow($this->currentActor->resolve(), $followable, onlyUnfollow: true);

        return response()->success($result);
    }

    /**
     * Resolve the followable from its morph alias, 404ing unknown targets.
     */
    private function resolveFollowable(string $type, int $id): Actor
    {
        $followable = match ($type) {
            'talent' => Talent::query()->find($id),
            'brand' => Brand::query()->find($id),
            default => null,
        };

        abort_if($followable === null, 404);

        return $followable;
    }
}
