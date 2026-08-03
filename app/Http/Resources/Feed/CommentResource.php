<?php

namespace App\Http\Resources\Feed;

use App\Feed\CurrentActor;
use App\Models\Comment;
use App\Services\BodyRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One comment (or reply) for listings (feed-spec §3): author identity, safe
 * rendered body HTML, counters, viewer interaction state, edit-window flag,
 * preview replies + remaining count. A trashed comment renders as a TOMBSTONE
 * (D12): no body, no author, thread intact.
 *
 * Callers eager-load: author.media, mentions.mentioned, linkedUrls.preview,
 * viewerLike (constrained likes), replies (limited, same loads). The viewer's
 * like state must be preloaded — this resource never queries.
 */
class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Comment $comment */
        $comment = $this->resource;

        if ($comment->trashed()) {
            return [
                'id' => $comment->id,
                'is_tombstone' => true,
                'body_html' => null,
                'author' => null,
                'replies_count' => $comment->replies_count,
                'replies' => CommentResource::collection($this->whenLoaded('replies')),
                'created_at' => $comment->created_at?->toIso8601String(),
            ];
        }

        return [
            'id' => $comment->id,
            'is_tombstone' => false,
            'post_id' => $comment->post_id,
            'parent_id' => $comment->parent_id,
            'body' => $comment->body,
            'body_html' => app(BodyRenderer::class)->render($comment),
            'author' => new ActorResource($comment->author),
            'state' => $comment->state->getValue(),
            'chips' => $this->when(
                $comment->relationLoaded('linkedUrls'),
                fn () => LinkChipResource::collection(
                    $comment->linkedUrls->sortBy('position')->pluck('preview')->filter()->values()
                ),
            ),
            'likes_count' => $comment->likes_count,
            'replies_count' => $comment->replies_count,
            'liked_by_viewer' => $this->when(
                $comment->relationLoaded('viewerLike'),
                fn (): bool => $comment->viewerLike->isNotEmpty(),
            ),
            'is_editable' => $comment->isEditable(),
            'is_mine' => $this->isMine($comment),
            'editable_until' => $comment->created_at
                ?->addMinutes((int) config('feed.edit_window_minutes'))->toIso8601String(),
            'edited_at' => $comment->edited_at?->toIso8601String(),
            'created_at' => $comment->created_at?->toIso8601String(),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'remaining_replies' => $this->when(
                $comment->relationLoaded('replies'),
                fn (): int => max(0, $comment->replies_count - $comment->replies->count()),
            ),
        ];
    }

    /**
     * Whether the current actor authored this comment (drives the inline
     * edit/delete affordances client-side; policies re-check server-side).
     */
    private function isMine(Comment $comment): bool
    {
        $actor = app(CurrentActor::class)->resolve();

        return $actor !== null
            && $comment->author_type === $actor->getMorphClass()
            && (string) $comment->author_id === (string) $actor->getKey();
    }
}
