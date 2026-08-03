<?php

namespace App\Http\Controllers\Feed;

use App\Actions\Feed\ToggleBookmark;
use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

/**
 * Post saves (5X): POST saves, DELETE unsaves — idempotent, race-safe on the
 * unique index. Collections are private (ADR-F9); reads live on the
 * dashboards, scoped to the current actor.
 */
class BookmarkController extends FeedBaseController
{
    /**
     * Save the post as the current actor.
     */
    public function store(Post $post, ToggleBookmark $toggleBookmark): JsonResponse
    {
        $this->gate()->authorize('create', Bookmark::class);
        abort_unless($this->gate()->allows('view', $post), 404);

        return response()->success($toggleBookmark($this->currentActor->resolve(), $post, onlySave: true));
    }

    /**
     * Withdraw the actor's save.
     */
    public function destroy(Post $post, ToggleBookmark $toggleBookmark): JsonResponse
    {
        $this->gate()->authorize('create', Bookmark::class);
        abort_unless($this->gate()->allows('view', $post), 404);

        return response()->success($toggleBookmark($this->currentActor->resolve(), $post, onlyUnsave: true));
    }
}
