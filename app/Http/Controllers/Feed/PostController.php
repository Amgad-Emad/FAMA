<?php

namespace App\Http\Controllers\Feed;

use App\Actions\Feed\CreatePost;
use App\Actions\Feed\DeletePost;
use App\Actions\Feed\TogglePinPost;
use App\Actions\Feed\UpdatePost;
use App\Data\Feed\CreatePostData;
use App\Http\Requests\Feed\StorePostRequest;
use App\Http\Requests\Feed\UpdatePostRequest;
use App\Http\Resources\Feed\PostResource;
use App\Models\Post;
use App\Models\Talent;
use App\Support\FeedComposer;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Post detail + the author write path (feed-spec §4, §12). Reads are public
 * (unpublished 404s — existence is not leaked); writes authorize through
 * PostPolicy and delegate to the Feed actions. The Daylight detail page
 * replaces the interim view in Prompt 5M.
 */
class PostController extends FeedBaseController
{
    /**
     * Render one post with its full graph eager-loaded.
     */
    public function show(Post $post): View
    {
        abort_unless($this->gate()->allows('view', $post), 404);

        $post->load($this->frameGraph());

        return view('feed.post', [
            'post' => $post,
            // The author edits from here too (fama-ui Edit post dialog).
            'composer' => FeedComposer::for($this->currentActor->resolve()),
        ]);
    }

    /**
     * The post as an Ajax fragment — the envelope-{html} contract the feed
     * shell swaps in place (talent-spec convention, ADR-R style).
     */
    public function fragment(Post $post): JsonResponse
    {
        abort_unless($this->gate()->allows('view', $post), 404);

        $post->load($this->frameGraph());

        return response()->success([
            // ?full=1 → the post-page variant (an edit made on the post page).
            'html' => view('feed.partials.post', ['post' => $post, 'full' => request()->boolean('full')])->render(),
        ]);
    }

    /**
     * The eager graph the canonical post card reads: author, media, skills,
     * project, mentions + link chips, and — only for a signed-in actor — the
     * viewer's like/save state (a guest skips both constrained queries, so the
     * detail page stays inside its fixed query budget).
     *
     * @return array<int|string, mixed>
     */
    private function frameGraph(): array
    {
        $graph = ['author.media', 'skills', 'project.media', 'linkedUrls.preview', 'mentions.mentioned', 'media'];
        $viewer = $this->currentActor->resolve();

        if ($viewer !== null) {
            $graph['viewerLike'] = fn ($query) => $query
                ->where('liker_type', $viewer->getMorphClass())
                ->where('liker_id', $viewer->getKey());
            $graph['viewerBookmark'] = fn ($query) => $query
                ->where('bookmarker_type', $viewer->getMorphClass())
                ->where('bookmarker_id', $viewer->getKey());
        }

        return $graph;
    }

    /**
     * The post as JSON (the API mirror's detail shape).
     */
    public function apiShow(Post $post): JsonResponse
    {
        abort_unless($this->gate()->allows('view', $post), 404);

        $post->load(['author.media', 'skills', 'project.media', 'mentions.mentioned', 'linkedUrls.preview', 'media']);

        return response()->success(new PostResource($post));
    }

    /**
     * A talent's published posts, newest first (public).
     */
    public function byTalent(string $username): JsonResponse
    {
        $talent = Talent::query()
            ->where('slug', $username)->where('is_published', true)->first();

        abort_if($talent === null, 404);

        $viewer = $this->currentActor->resolve();
        $viewerLike = $viewer === null
            ? fn ($query) => $query->whereRaw('1 = 0')
            : fn ($query) => $query
                ->where('liker_type', $viewer->getMorphClass())
                ->where('liker_id', $viewer->getKey());

        $viewerBookmark = $viewer === null
            ? fn ($query) => $query->whereRaw('1 = 0')
            : fn ($query) => $query
                ->where('bookmarker_type', $viewer->getMorphClass())
                ->where('bookmarker_id', $viewer->getKey());

        $posts = $talent->posts()
            ->published()
            ->with(['author.media', 'media', 'skills', 'project.media', 'mentions.mentioned', 'linkedUrls.preview', 'viewerLike' => $viewerLike, 'viewerBookmark' => $viewerBookmark])
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->paginate(12);

        return response()->paginated($posts, PostResource::collection($posts->getCollection()));
    }

    /**
     * Create a note or media post as the current actor.
     */
    public function store(StorePostRequest $request, CreatePost $createPost): JsonResponse
    {
        $this->gate()->authorize('create', Post::class);

        $post = $createPost($this->currentActor->resolve(), new CreatePostData(
            postType: $request->string('post_type')->toString(),
            body: $request->filled('body') ? $request->string('body')->toString() : null,
            skillIds: array_map(intval(...), $request->input('skill_ids', [])),
            media: $request->file('media', []),
            mediaAlt: array_map(strval(...), $request->input('media_alt', [])),
        ));

        return response()->success($post, status: 201);
    }

    /**
     * Edit the post inside the 15-minute window (media immutable).
     */
    public function update(Post $post, UpdatePostRequest $request, UpdatePost $updatePost): JsonResponse
    {
        $this->gate()->authorize('update', $post);

        $updated = $updatePost(
            $post,
            $request->filled('body') ? $request->string('body')->toString() : null,
            $request->has('skill_ids') ? array_map(intval(...), $request->input('skill_ids', [])) : null,
        );

        return response()->success($updated);
    }

    /**
     * Soft-delete the post (author or moderating admin).
     */
    public function destroy(Post $post, DeletePost $deletePost): JsonResponse
    {
        $this->gate()->authorize('delete', $post);

        $deletePost($post);

        return response()->success(message: __('Post deleted.'));
    }

    /**
     * Pin/unpin the post on the author's presence (max one pinned).
     */
    public function pin(Post $post, TogglePinPost $togglePin): JsonResponse
    {
        $this->gate()->authorize('pin', $post);

        return response()->success($togglePin($post));
    }
}
