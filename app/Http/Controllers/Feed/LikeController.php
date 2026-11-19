<?php

namespace App\Http\Controllers\Feed;

use App\Actions\Feed\ToggleLike;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

/**
 * Likes on posts AND comments (feed-spec §2): POST likes, DELETE un-likes —
 * both idempotent (the action is race-safe on the unique index).
 */
class LikeController extends FeedBaseController
{
    /**
     * Like the post as the current actor.
     */
    public function store(Post $post, ToggleLike $toggleLike): JsonResponse
    {
        $this->gate()->authorize('create', Like::class);
        abort_unless($this->gate()->allows('view', $post), 404);

        $result = $toggleLike($this->currentActor->resolve(), $post, onlyLike: true);

        return response()->success($result);
    }

    /**
     * Withdraw the actor's like from the post.
     */
    public function destroy(Post $post, ToggleLike $toggleLike): JsonResponse
    {
        $this->gate()->authorize('create', Like::class);
        abort_unless($this->gate()->allows('view', $post), 404);

        $result = $toggleLike($this->currentActor->resolve(), $post, onlyUnlike: true);

        return response()->success($result);
    }

    /**
     * Like a comment as the current actor.
     */
    public function storeComment(Comment $comment, ToggleLike $toggleLike): JsonResponse
    {
        $this->gate()->authorize('create', Like::class);
        abort_unless($this->gate()->allows('view', $comment->post), 404);

        $result = $toggleLike($this->currentActor->resolve(), $comment, onlyLike: true);

        return response()->success($result);
    }

    /**
     * Withdraw the actor's like from the comment.
     */
    public function destroyComment(Comment $comment, ToggleLike $toggleLike): JsonResponse
    {
        $this->gate()->authorize('create', Like::class);
        abort_unless($this->gate()->allows('view', $comment->post), 404);

        $result = $toggleLike($this->currentActor->resolve(), $comment, onlyUnlike: true);

        return response()->success($result);
    }

    /**
     * Like a project (ADR-AJ). Only a project on a published profile can be liked.
     */
    public function storeProject(Project $project, ToggleLike $toggleLike): JsonResponse
    {
        $this->gate()->authorize('create', Like::class);
        abort_unless($this->projectIsPublic($project), 404);

        return response()->success($toggleLike($this->currentActor->resolve(), $project, onlyLike: true));
    }

    /**
     * Withdraw a like on a project (ADR-AJ).
     */
    public function destroyProject(Project $project, ToggleLike $toggleLike): JsonResponse
    {
        $this->gate()->authorize('create', Like::class);
        abort_unless($this->projectIsPublic($project), 404);

        return response()->success($toggleLike($this->currentActor->resolve(), $project, onlyUnlike: true));
    }

    /**
     * A project is public when its owner's profile is published.
     */
    private function projectIsPublic(Project $project): bool
    {
        return (bool) $project->talent()->value('is_published');
    }
}
