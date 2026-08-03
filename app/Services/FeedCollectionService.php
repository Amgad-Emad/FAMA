<?php

namespace App\Services;

use App\Feed\Actor;
use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The private dashboard collections (5X, ADR-F9): an actor's SAVED and LIKED
 * posts — newest interaction first, paginated 12, server-side skill filter +
 * body search, fully eager-loaded (fixed query count asserted in tests).
 * Every query is scoped to the given actor; there is no way to request
 * someone else's collection.
 */
class FeedCollectionService extends Service
{
    protected string $logChannel = 'feed';

    /**
     * The actor's saved posts.
     *
     * @return LengthAwarePaginator<int, Post>
     */
    public function saved(Actor $actor, ?string $skill = null, ?string $q = null): LengthAwarePaginator
    {
        $postIds = Bookmark::query()
            ->where('bookmarker_type', $actor->getMorphClass())
            ->where('bookmarker_id', $actor->getKey())
            ->where('bookmarkable_type', 'post')
            ->latest('created_at')
            ->pluck('bookmarkable_id');

        return $this->hydrate($postIds->all(), $skill, $q);
    }

    /**
     * The actor's liked posts.
     *
     * @return LengthAwarePaginator<int, Post>
     */
    public function liked(Actor $actor, ?string $skill = null, ?string $q = null): LengthAwarePaginator
    {
        $postIds = Like::query()
            ->where('liker_type', $actor->getMorphClass())
            ->where('liker_id', $actor->getKey())
            ->where('likeable_type', 'post')
            ->latest('created_at')
            ->pluck('likeable_id');

        return $this->hydrate($postIds->all(), $skill, $q);
    }

    /**
     * Hydrate an interaction-ordered ID list with filters, keeping the order.
     *
     * @param  list<int>  $ids
     * @return LengthAwarePaginator<int, Post>
     */
    private function hydrate(array $ids, ?string $skill, ?string $q): LengthAwarePaginator
    {
        if ($ids === []) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12);
        }

        return Post::query()
            ->published()
            ->whereIn('id', $ids)
            ->when($skill !== null && $skill !== '', fn ($query) => $query->whereHas(
                'skills', fn ($sub) => $sub->where('slug', $skill)
            ))
            ->when($q !== null && $q !== '', fn ($query) => $query->where('body', 'like', '%'.$q.'%'))
            ->with(['author.media', 'media', 'skills'])
            ->orderByRaw('FIELD(id, '.implode(',', array_map(intval(...), $ids)).')')
            ->paginate(12);
    }

    /**
     * The compact card payload the collection views render.
     *
     * @param  LengthAwarePaginator<int, Post>  $posts
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function present(LengthAwarePaginator $posts): \Illuminate\Support\Collection
    {
        return $posts->getCollection()->map(function (Post $post): array {
            $cover = $post->getMedia('post_media')->first();

            return [
                'id' => $post->id,
                'cover_url' => $cover !== null
                    ? ($cover->hasGeneratedConversion('thumb') ? $cover->getUrl('thumb') : $cover->getUrl())
                    : ($post->shared_snapshot['cover_media_path'] ?? null),
                'excerpt' => str(strip_tags((string) $post->body))->limit(110)->toString(),
                'post_type' => $post->post_type,
                'author' => $post->author?->feedDisplayName(),
                'author_username' => $post->author?->feedUsername(),
                'author_avatar' => $post->author?->feedAvatarUrl(),
                'published_at' => $post->published_at?->isoFormat('DD MMM YYYY'),
                'skills' => $post->skills->map(fn ($skill) => ['name' => $skill->name, 'slug' => $skill->slug])->values(),
                'permalink' => route('posts.show', $post->id),
            ];
        });
    }
}
