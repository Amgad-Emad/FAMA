<?php

namespace App\Http\Controllers\Feed;

use App\Actions\Feed\ToggleLike;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
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
}
