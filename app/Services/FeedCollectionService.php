<?php

namespace App\Services;

use App\Feed\Actor;
use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
     * Load the rest of the canonical post-card graph onto a collection page
     * (fama-ui Activity renders full cards): project, mentions, link chips and
     * the viewer's like + save state — constrained eager loads, no per-row
     * queries.
     *
     * @param  LengthAwarePaginator<int, Post>  $posts
     */
    public function withCardGraph(LengthAwarePaginator $posts, Actor $viewer): void
    {
        // An empty collection page is a plain Support collection — wrap the
        // items so eager loading works either way (same model instances).
        (new \Illuminate\Database\Eloquent\Collection($posts->items()))->load([
            'project.media',
            'mentions.mentioned',
            'linkedUrls.preview',
            'viewerLike' => fn ($query) => $query
                ->where('liker_type', $viewer->getMorphClass())
                ->where('liker_id', $viewer->getKey()),
            'viewerBookmark' => fn ($query) => $query
                ->where('bookmarker_type', $viewer->getMorphClass())
                ->where('bookmarker_id', $viewer->getKey()),
        ]);
    }

    /**
     * How many published posts the actor has saved and liked (the Activity
     * tab counts) — one count query each.
     *
     * @return array{saved: int, liked: int}
     */
    public function counts(Actor $actor): array
    {
        $published = Post::query()->published()->select('posts.id');

        return [
            'saved' => Bookmark::query()
                ->where('bookmarker_type', $actor->getMorphClass())
                ->where('bookmarker_id', $actor->getKey())
                ->where('bookmarkable_type', 'post')
                ->whereIn('bookmarkable_id', $published)
                ->count(),
            'liked' => Like::query()
                ->where('liker_type', $actor->getMorphClass())
                ->where('liker_id', $actor->getKey())
                ->where('likeable_type', 'post')
                ->whereIn('likeable_id', $published)
                ->count(),
        ];
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
            return new LengthAwarePaginator([], 0, 12);
        }

        return Post::query()
            ->published()
            ->whereIn('id', $ids)
            ->when($skill !== null && $skill !== '', fn ($query) => $query->whereHas(
                'skills', fn ($sub) => $sub->where('slug', $skill)
            ))
            ->when($q !== null && $q !== '', fn ($query) => $query->where('body', 'like', '%'.$q.'%'))
            ->with(['author.media', 'media', 'skills', 'repostOf.author.media', 'repostOf.media', 'repostOf.project.media'])
            ->orderByRaw('FIELD(id, '.implode(',', array_map(intval(...), $ids)).')')
            ->paginate(12);
    }

    /**
     * The compact card payload the collection views render.
     *
     * @param  LengthAwarePaginator<int, Post>  $posts
     * @return Collection<int, array<string, mixed>>
     */
    public function present(LengthAwarePaginator $posts): Collection
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
