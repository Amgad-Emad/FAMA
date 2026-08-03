<?php

namespace App\Http\Controllers\Feed;

use App\Actions\Feed\CreateComment;
use App\Actions\Feed\DeleteComment;
use App\Actions\Feed\UpdateComment;
use App\Http\Requests\Feed\StoreCommentRequest;
use App\Http\Resources\Feed\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Closure;
use Illuminate\Http\JsonResponse;

/**
 * Comment threads (feed-spec §3, ADR-F7). Listing: top-level comments
 * paginated 10, newest first, each with up to 3 preview replies + remaining
 * count; deleted parents with surviving replies stay as tombstones (D12);
 * every row carries the viewer's like state — all eager-loaded, nothing
 * per-row. Replies paginate separately. Writes go through the Feed actions
 * behind CommentPolicy.
 */
class CommentController extends FeedBaseController
{
    /**
     * Paginated top-level thread with preview replies and viewer state.
     */
    public function index(Post $post): JsonResponse
    {
        abort_unless($this->gate()->allows('view', $post), 404);

        $comments = Comment::withTrashed()
            ->where('post_id', $post->id)
            ->whereNull('parent_id')
            ->where(fn ($query) => $query
                ->whereNull('deleted_at')
                ->orWhereHas('replies'))
            ->with($this->threadLoads(previewReplies: true))
            ->latest()
            ->paginate(10);

        return response()->paginated($comments, CommentResource::collection($comments->getCollection()));
    }

    /**
     * A comment's replies, paginated separately, oldest first.
     */
    public function replies(Comment $comment): JsonResponse
    {
        abort_unless($this->gate()->allows('view', $comment->post), 404);

        $replies = $comment->replies()
            ->with($this->threadLoads(previewReplies: false))
            ->oldest()
            ->paginate(10);

        return response()->paginated($replies, CommentResource::collection($replies->getCollection()));
    }

    /**
     * Create a comment (or reply) on the post as the current actor.
     */
    public function store(Post $post, StoreCommentRequest $request, CreateComment $createComment): JsonResponse
    {
        $this->gate()->authorize('create', Comment::class);
        abort_unless($this->gate()->allows('view', $post), 404);

        $parent = $request->integer('parent_id') !== 0
            ? Comment::query()->find($request->integer('parent_id'))
            : null;

        $comment = $createComment(
            $this->currentActor->resolve(),
            $post,
            $request->string('body')->toString(),
            $parent,
        );

        $comment->load($this->threadLoads(previewReplies: false));

        return response()->success(new CommentResource($comment), status: 201);
    }

    /**
     * Edit a comment inside the 15-minute window.
     */
    public function update(Comment $comment, StoreCommentRequest $request, UpdateComment $updateComment): JsonResponse
    {
        $this->gate()->authorize('update', $comment);

        $updated = $updateComment($comment, $request->string('body')->toString());
        $updated->load($this->threadLoads(previewReplies: false));

        return response()->success(new CommentResource($updated));
    }

    /**
     * Delete a comment (tombstoned when it has replies).
     */
    public function destroy(Comment $comment, DeleteComment $deleteComment): JsonResponse
    {
        $this->gate()->authorize('delete', $comment);

        $deleteComment($comment);

        return response()->success(message: __('Comment deleted.'));
    }

    /**
     * The full eager-load set for thread rows — author identity, rendered-
     * body inputs, and the viewer's like state. One shape for every listing
     * so the query count is fixed regardless of page size.
     *
     * @return array<int|string, mixed>
     */
    private function threadLoads(bool $previewReplies): array
    {
        $viewerLike = $this->viewerLikeConstraint();

        $loads = [
            'author.media',
            'mentions.mentioned',
            'linkedUrls.preview',
            'viewerLike' => $viewerLike,
        ];

        if ($previewReplies) {
            $loads['replies'] = fn ($query) => $query
                ->oldest()
                ->limit(3)
                ->with([
                    'author.media',
                    'mentions.mentioned',
                    'linkedUrls.preview',
                    'viewerLike' => $viewerLike,
                ]);
        }

        return $loads;
    }

    /**
     * Constrain a likes eager-load to the current actor (empty for guests).
     */
    private function viewerLikeConstraint(): Closure
    {
        $actor = $this->currentActor->resolve();

        return $actor === null
            ? fn ($query) => $query->whereRaw('1 = 0')
            : fn ($query) => $query
                ->where('liker_type', $actor->getMorphClass())
                ->where('liker_id', $actor->getKey());
    }
}
