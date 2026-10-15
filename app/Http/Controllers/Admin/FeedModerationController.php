<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ContentRemovedNotice;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostReport;
use App\Models\Talent;
use App\Services\FeedModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Feed moderation console (Prompt 5Q, permission moderate-content): the
 * reports queue (status/reason/target filters, content in context), posts and
 * comments indexes (state filters, hide/remove/restore), and the talent
 * activity timeline. Blade shell + Ajax data endpoints, matching the admin
 * panel's queue pattern (server-validated ?queue= deep links).
 */
class FeedModerationController extends Controller
{
    public function __construct(private readonly FeedModerationService $moderation) {}

    /**
     * The console shell. ?queue= deep-links a tab (server-validated).
     */
    public function index(Request $request): View
    {
        $queue = $request->query('queue', 'posts');

        if (! in_array($queue, ['reports', 'posts', 'comments'], true)) {
            $queue = 'posts';
        }

        // Tab badges: everything that still needs a look.
        $counts = [
            'posts' => Post::query()->count(),
            'reports' => PostReport::query()->whereIn('status', ['open', 'reviewing'])->count(),
            'comments' => Comment::query()->count(),
            'hidden' => Post::query()->where('state', 'hidden')->count(),
        ];

        return view('admin.feed.index', ['queue' => $queue, 'counts' => $counts]);
    }

    /**
     * Reports queue data: filter by status / reason / target type.
     */
    public function reports(Request $request): JsonResponse
    {
        $reports = PostReport::query()
            ->when(in_array($request->query('status'), ['open', 'reviewing', 'actioned', 'dismissed'], true),
                fn ($query) => $query->where('status', $request->query('status')))
            ->when(in_array($request->query('reason'), config('feed.report_reasons'), true),
                fn ($query) => $query->where('reason', $request->query('reason')))
            ->when(in_array($request->query('target'), ['post', 'comment'], true),
                fn ($query) => $query->where('reportable_type', $request->query('target')))
            ->with(['reportable' => fn ($morphTo) => $morphTo->constrain([
                Post::class => fn ($query) => $query->withTrashed(),
                Comment::class => fn ($query) => $query->withTrashed(),
            ]), 'reporter', 'resolver'])
            ->latest()
            ->paginate(20);

        $rows = $reports->getCollection()->map(function (PostReport $report): array {
            $reportable = $report->reportable;

            return [
                'id' => $report->id,
                'target_type' => $report->reportable_type,
                'target_id' => $report->reportable_id,
                'reason' => $report->reason,
                'note' => $report->note,
                'status' => $report->status,
                'reporter' => $report->reporter?->feedDisplayName(),
                'created_at' => $report->created_at?->toDayDateTimeString(),
                'resolved_by' => $report->resolver?->name,
                'content' => $reportable !== null
                    ? str(strip_tags((string) $reportable->body))->limit(160)->toString()
                    : null,
                'content_state' => $reportable?->state?->getValue(),
                'auto_hidden' => $reportable !== null && $reportable->state->getValue() === 'hidden'
                    && $this->moderation->wasAutoHidden($reportable),
                'permalink' => $report->reportable_type === 'post'
                    ? route('posts.show', $report->reportable_id)
                    : null,
            ];
        });

        return response()->paginated($reports, $rows);
    }

    /**
     * Posts index: search + state filter.
     */
    public function posts(Request $request): JsonResponse
    {
        $posts = Post::withTrashed()
            ->when(in_array($request->query('state'), ['draft', 'published', 'hidden', 'removed'], true),
                fn ($query) => $query->where('state', $request->query('state')))
            ->when($request->filled('q'), fn ($query) => $query->where('body', 'like', '%'.$request->query('q').'%'))
            ->with(['author.media', 'media'])
            ->withCount(['reports as open_reports' => fn ($q) => $q->whereIn('status', ['open', 'reviewing'])])
            ->latest('id')
            ->paginate(20);

        $rows = $posts->getCollection()->map(fn (Post $post): array => [
            'id' => $post->id,
            'author' => $post->author?->feedDisplayName(),
            'author_username' => $post->author?->feedUsername(),
            'author_avatar' => $post->author?->feedAvatarUrl(),
            'thumb' => ($m = $post->getFirstMedia('post_media')) ? ($m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : (str_starts_with((string) $m->mime_type, 'image/') ? $m->getUrl() : null)) : null,
            'media_count' => $post->getMedia('post_media')->count(),
            'open_reports' => (int) $post->open_reports,
            'type' => $post->post_type,
            'excerpt' => str(strip_tags((string) $post->body))->limit(120)->toString(),
            'state' => $post->state->getValue(),
            'deleted' => $post->deleted_at !== null,
            'likes' => $post->likes_count,
            'comments' => $post->comments_count,
            'published_at' => $post->published_at?->toDayDateTimeString(),
            'ago' => ($post->published_at ?? $post->created_at)?->diffForHumans(),
            'permalink' => route('posts.show', $post->id),
        ]);

        return response()->paginated($posts, $rows);
    }

    /**
     * Comments index.
     */
    public function comments(Request $request): JsonResponse
    {
        $comments = Comment::withTrashed()
            ->when(in_array($request->query('state'), ['published', 'hidden', 'removed'], true),
                fn ($query) => $query->where('state', $request->query('state')))
            ->when($request->filled('q'), fn ($query) => $query->where('body', 'like', '%'.$request->query('q').'%'))
            ->with(['author.media'])
            ->withCount(['reports as open_reports' => fn ($q) => $q->whereIn('status', ['open', 'reviewing'])])
            ->latest('id')
            ->paginate(20);

        $rows = $comments->getCollection()->map(fn (Comment $comment): array => [
            'id' => $comment->id,
            'post_id' => $comment->post_id,
            'author' => $comment->author?->feedDisplayName(),
            'author_username' => $comment->author?->feedUsername(),
            'author_avatar' => $comment->author?->feedAvatarUrl(),
            'open_reports' => (int) $comment->open_reports,
            'ago' => $comment->created_at?->diffForHumans(),
            'excerpt' => str($comment->body)->limit(120)->toString(),
            'state' => $comment->state->getValue(),
            'deleted' => $comment->deleted_at !== null,
            'created_at' => $comment->created_at?->toDayDateTimeString(),
            'permalink' => route('posts.show', $comment->post_id).'#comment-'.$comment->id,
        ]);

        return response()->paginated($comments, $rows);
    }

    /**
     * One talent's feed activity — posts, comments and reports against them,
     * as a single timeline.
     */
    public function talentActivity(Talent $talent): View
    {
        $posts = $talent->posts()->withTrashed()->latest('id')->limit(50)->get();
        $comments = $talent->feedComments()->withTrashed()->latest('id')->limit(50)->get();
        $reports = PostReport::query()
            ->where(fn ($query) => $query
                ->whereIn('reportable_id', $posts->pluck('id'))->where('reportable_type', 'post'))
            ->orWhere(fn ($query) => $query
                ->whereIn('reportable_id', $comments->pluck('id'))->where('reportable_type', 'comment'))
            ->latest()->limit(50)->get();

        $timeline = collect()
            ->concat($posts->map(fn ($post) => ['kind' => 'post', 'at' => $post->created_at, 'row' => $post]))
            ->concat($comments->map(fn ($comment) => ['kind' => 'comment', 'at' => $comment->created_at, 'row' => $comment]))
            ->concat($reports->map(fn ($report) => ['kind' => 'report', 'at' => $report->created_at, 'row' => $report]))
            ->sortByDesc('at')
            ->values();

        return view('admin.feed.talent', ['talent' => $talent, 'timeline' => $timeline]);
    }

    /**
     * Post actions: hide / remove / restore.
     */
    public function moderatePost(Request $request, Post $post, string $action): JsonResponse
    {
        $reason = trim((string) $request->input('reason', ''));
        $notice = $action === 'remove' ? $this->removalNoticeFrom($request) : null; // validated BEFORE the removal

        match ($action) {
            'hide' => $this->moderation->hidePost($post, $request->user(), $reason ?: null),
            'remove' => $this->moderation->removePost($post, $request->user(), $reason ?: __('a community guidelines violation')),
            'restore' => $this->moderation->restorePost($post, $request->user()),
            'delete' => $this->moderation->deletePost($post, $request->user(), $reason ?: null),
            default => abort(404),
        };
        $this->sendRemovalNotice($notice, 'post', $post, $reason);

        return response()->success(['state' => $post->refresh()->state->getValue(), 'deleted' => $post->trashed()], __('Done.'));
    }

    /**
     * Comment actions: hide / remove / restore.
     */
    public function moderateComment(Request $request, Comment $comment, string $action): JsonResponse
    {
        $reason = trim((string) $request->input('reason', ''));
        $notice = $action === 'remove' ? $this->removalNoticeFrom($request) : null;

        match ($action) {
            'hide' => $this->moderation->hideComment($comment, $request->user(), $reason ?: null),
            'remove' => $this->moderation->removeComment($comment, $request->user(), $reason ?: __('a community guidelines violation')),
            'restore' => $this->moderation->restoreComment($comment, $request->user()),
            'delete' => $this->moderation->deleteComment($comment, $request->user(), $reason ?: null),
            default => abort(404),
        };
        $this->sendRemovalNotice($notice, 'comment', $comment, $reason);

        return response()->success(['state' => $comment->refresh()->state->getValue(), 'deleted' => $comment->trashed()], __('Done.'));
    }

    /**
     * The email that goes with "Remove & tell the author": suggested copy for
     * this post/comment and the rendered preview of the copy sent (defaults
     * when none). Sends nothing.
     */
    public function removalNotice(Request $request, string $kind, int $id): JsonResponse
    {
        $content = $kind === 'posts' ? Post::withTrashed()->findOrFail($id) : Comment::withTrashed()->findOrFail($id);
        $author = $content->author()->withoutGlobalScopes()->first();
        $type = $kind === 'posts' ? 'post' : 'comment';
        $name = $author?->feedDisplayName() ?? '';

        $defaults = [
            'email' => (string) ($author?->email ?? ''),
            'subject' => $type === 'post' ? __('Your post on Fama was removed') : __('Your comment on Fama was removed'),
            'message' => __("Hi :name,\n\nOur team removed your :kind because it doesn't follow the Fama community guidelines. You can keep posting — just make sure what you share respects them.", ['name' => $name, 'kind' => $type === 'post' ? __('post') : __('comment')]),
        ];
        $copy = $request->validate([
            'subject' => ['sometimes', 'nullable', 'string', 'max:150'],
            'message' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $mail = new ContentRemovedNotice($type, $copy['subject'] ?? $defaults['subject'], $copy['message'] ?? $defaults['message'],
            (string) ($copy['reason'] ?? ''), $this->excerpt($content));

        return response()->success(['defaults' => $defaults, 'html' => $mail->render()]);
    }

    /**
     * The removal email the admin composed (`notify`), validated; null when
     * "Send an email" is off. Called before the removal so a bad address never
     * half-applies it.
     *
     * @return array<string, string>|null
     */
    private function removalNoticeFrom(Request $request): ?array
    {
        if (! $request->boolean('notify.send')) {
            return null;
        }

        return $request->validate([
            'notify.email' => ['required', 'email', 'max:255'],
            'notify.subject' => ['required', 'string', 'max:150'],
            'notify.message' => ['required', 'string', 'max:3000'],
        ])['notify'];
    }

    /** Queue the removal email (after commit) when one was composed. */
    private function sendRemovalNotice(?array $notice, string $type, Post|Comment $content, string $reason): void
    {
        if ($notice === null) {
            return;
        }

        Mail::to($notice['email'])->queue(new ContentRemovedNotice($type, $notice['subject'], $notice['message'], $reason, $this->excerpt($content)));
    }

    /** What was removed, as a short plain-text quote. */
    private function excerpt(Post|Comment $content): string
    {
        return str(strip_tags((string) $content->body))->squish()->limit(280)->toString();
    }

    /**
     * One action on many posts or comments: hide / restore / delete (≤200).
     * Impossible moves (already hidden…) are skipped and counted.
     */
    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'in:post,comment'],
            'action' => ['required', 'in:hide,restore,delete'],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ]);

        [$done, $skipped] = $this->moderation->bulk($request->user(), $data['kind'], $data['action'], array_map('intval', $data['ids']));

        return response()->success(['done' => $done, 'skipped' => $skipped], __('Done.'));
    }

    /**
     * Report resolution: actioned / dismissed.
     */
    public function resolveReport(Request $request, PostReport $report, string $resolution): JsonResponse
    {
        $this->moderation->resolveReport($report, $request->user(), $resolution);

        return response()->success(['status' => $report->refresh()->status], __('Done.'));
    }
}
