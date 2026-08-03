<?php

namespace App\Http\Resources\Feed;

use App\Models\Post;
use App\Services\BodyRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One post for feed surfaces (feed-spec §4): author identity, safe rendered
 * body, ordered media derivatives, skill tags (the credits line), the live
 * Project reference with its snapshot fallback (ADR-F2), counters and the
 * viewer's interaction state — so the frontend never makes a second call.
 *
 * Callers eager-load: author.media, media, skills, project.media,
 * mentions.mentioned, linkedUrls.preview, viewerLike (constrained).
 */
class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Post $post */
        $post = $this->resource;

        return [
            'id' => $post->id,
            'post_type' => $post->post_type,
            'author' => new ActorResource($post->author),
            'body' => $post->body,
            'body_html' => filled($post->body) ? app(BodyRenderer::class)->render($post) : null,
            'media' => $post->getMedia('post_media')->map(fn (Media $media): array => [
                'id' => $media->id,
                'type' => str_starts_with((string) $media->mime_type, 'video/') ? 'video' : 'image',
                'original_url' => $media->getUrl(),
                'thumb_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
                'feed_url' => $media->hasGeneratedConversion('feed') ? $media->getUrl('feed') : $media->getUrl(),
                'full_url' => $media->hasGeneratedConversion('full') ? $media->getUrl('full') : $media->getUrl(),
                'poster_url' => $media->hasGeneratedConversion('poster') ? $media->getUrl('poster') : null,
            ])->values(),
            'skills' => $post->skills->map(fn ($skill): array => [
                'id' => $skill->id,
                'name' => $skill->name,
                'slug' => $skill->slug,
                'icon' => $skill->icon,
            ])->values(),
            'project' => $this->projectPayload($post),
            'chips' => $this->when(
                $post->relationLoaded('linkedUrls'),
                fn () => LinkChipResource::collection(
                    $post->linkedUrls->sortBy('position')->pluck('preview')->filter()->values()
                ),
            ),
            'likes_count' => $post->likes_count,
            'comments_count' => $post->comments_count,
            'shares_count' => $post->shares_count,
            'liked_by_viewer' => $this->when(
                $post->relationLoaded('viewerLike'),
                fn (): bool => $post->viewerLike->isNotEmpty(),
            ),
            'bookmarks_count' => $post->bookmarks_count,
            'saved_by_viewer' => $this->when(
                $post->relationLoaded('viewerBookmark'),
                fn (): bool => $post->viewerBookmark->isNotEmpty(),
            ),
            'is_pinned' => $post->is_pinned,
            'is_editable' => $post->isEditable(),
            'edited_at' => $post->edited_at?->toIso8601String(),
            'published_at' => $post->published_at?->toIso8601String(),
            'permalink' => route('posts.show', $post),
        ];
    }

    /**
     * The Project spread payload (ADR-F2): the LIVE Project when it still
     * exists and is reachable; otherwise the frozen snapshot as a dead-but-
     * intact frame (no link-through).
     *
     * @return array<string, mixed>|null
     */
    private function projectPayload(Post $post): ?array
    {
        if ($post->post_type !== 'project_share') {
            return null;
        }

        $project = $post->relationLoaded('project') ? $post->project : null;

        if ($project !== null) {
            return [
                'is_live' => true,
                'id' => $project->id,
                'title' => $project->title,
                'summary' => $project->summary,
                'cover_url' => $project->cover_image_url,
                'year' => $project->year,
                'url' => $post->author !== null
                    ? route('talent.work', ['slug' => $post->author->feedUsername(), 'project' => $project->id])
                    : null,
            ];
        }

        $snapshot = $post->shared_snapshot ?? [];

        return [
            'is_live' => false,
            'title' => $snapshot['title'][app()->getLocale()] ?? $snapshot['title']['en'] ?? null,
            'cover_url' => $snapshot['cover_media_path'] ?? null,
            'shared_at' => $snapshot['shared_at'] ?? null,
            'url' => null,
        ];
    }
}
