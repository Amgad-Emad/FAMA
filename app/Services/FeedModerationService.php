<?php

namespace App\Services;

use App\Feed\Actor;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostReport;
use App\Models\User;
use App\Notifications\Feed\ContentRemoved;
use App\States\Comment as CommentStates;
use App\States\Post as PostStates;
use Illuminate\Database\Eloquent\Model;

/**
 * Feed moderation (Prompt 5Q, feed-spec §10). Every action runs through the
 * state machines, inside a transaction, activity-logged (`feed_moderation`)
 * with the acting admin as causer; removals notify the author WITH the
 * reason. The auto-hide threshold (config feed.auto_hide_reports) trips here,
 * from the report write path.
 */
class FeedModerationService extends Service
{
    protected string $logChannel = 'feed';

    /**
     * Hide a published post (it leaves the feed, stays restorable).
     */
    public function hidePost(Post $post, User $admin, ?string $reason = null): void
    {
        $this->runInTransaction(function () use ($post, $admin, $reason): void {
            $post->state->transitionTo(PostStates\Hidden::class);
            $this->audit('post.hidden', $post, $admin, $reason);
        }, ['post' => $post->id, 'action' => 'hide']);
    }

    /**
     * Remove a post (terminal) and tell the author why.
     */
    public function removePost(Post $post, User $admin, string $reason): void
    {
        $this->runInTransaction(function () use ($post, $admin, $reason): void {
            $post->state->transitionTo(PostStates\Removed::class);
            $this->audit('post.removed', $post, $admin, $reason);
        }, ['post' => $post->id, 'action' => 'remove']);

        $this->notifyAuthor($post, __('Your post was removed'), $reason);
    }

    /**
     * Restore a hidden post to the feed.
     */
    public function restorePost(Post $post, User $admin): void
    {
        $this->runInTransaction(function () use ($post, $admin): void {
            $post->state->transitionTo(PostStates\Published::class);
            $this->audit('post.restored', $post, $admin);
        }, ['post' => $post->id, 'action' => 'restore']);
    }

    /**
     * Hide a comment.
     */
    public function hideComment(Comment $comment, User $admin, ?string $reason = null): void
    {
        $this->runInTransaction(function () use ($comment, $admin, $reason): void {
            $comment->state->transitionTo(CommentStates\Hidden::class);
            $this->audit('comment.hidden', $comment, $admin, $reason);
        }, ['comment' => $comment->id, 'action' => 'hide']);
    }

    /**
     * Remove a comment (terminal) and tell the author why.
     */
    public function removeComment(Comment $comment, User $admin, string $reason): void
    {
        $this->runInTransaction(function () use ($comment, $admin, $reason): void {
            $comment->state->transitionTo(CommentStates\Removed::class);
            $this->audit('comment.removed', $comment, $admin, $reason);
        }, ['comment' => $comment->id, 'action' => 'remove']);

        $this->notifyAuthor($comment, __('Your comment was removed'), $reason);
    }

    /**
     * Restore a hidden comment.
     */
    public function restoreComment(Comment $comment, User $admin): void
    {
        $this->runInTransaction(function () use ($comment, $admin): void {
            $comment->state->transitionTo(CommentStates\Published::class);
            $this->audit('comment.restored', $comment, $admin);
        }, ['comment' => $comment->id, 'action' => 'restore']);
    }

    /**
     * Resolve a report: actioned (content dealt with) or dismissed.
     */
    public function resolveReport(PostReport $report, User $admin, string $resolution): void
    {
        if (! in_array($resolution, ['actioned', 'dismissed'], true)) {
            throw new \InvalidArgumentException(__('Unknown resolution.'));
        }

        $this->runInTransaction(function () use ($report, $admin, $resolution): void {
            $report->forceFill([
                'status' => $resolution,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ])->save();

            $this->audit('report.'.$resolution, $report, $admin);
        }, ['report' => $report->id, 'action' => $resolution]);
    }

    /**
     * The auto-hide tripwire (feed-spec §10): called after every new report;
     * when open reports on one item reach the threshold, the item hides
     * pending review and enters the queue flagged auto-hidden.
     */
    public function registerReport(Post|Comment $reportable): void
    {
        $threshold = (int) config('feed.auto_hide_reports');

        $open = PostReport::query()
            ->where('reportable_type', $reportable->getMorphClass())
            ->where('reportable_id', $reportable->getKey())
            ->where('status', 'open')
            ->count();

        if ($open < $threshold) {
            return;
        }

        $isHidden = $reportable->state->getValue() === 'hidden';

        if ($isHidden) {
            return;
        }

        if ($reportable->state->getValue() !== 'published') {
            return;
        }

        $this->runInTransaction(function () use ($reportable): void {
            $reportable->state->transitionTo(
                $reportable instanceof Post ? PostStates\Hidden::class : CommentStates\Hidden::class,
            );

            activity('feed_moderation')
                ->performedOn($reportable)
                ->withProperties(['auto_hidden' => true, 'open_reports' => (int) config('feed.auto_hide_reports')])
                ->log('auto_hidden');
        }, ['reportable' => $reportable->getMorphClass().':'.$reportable->getKey(), 'action' => 'auto-hide']);
    }

    /**
     * Whether a hidden item was auto-hidden (drives the queue flag).
     */
    public function wasAutoHidden(Post|Comment $reportable): bool
    {
        return \Spatie\Activitylog\Models\Activity::query()
            ->where('log_name', 'feed_moderation')
            ->where('subject_type', $reportable->getMorphClass())
            ->where('subject_id', $reportable->getKey())
            ->where('description', 'auto_hidden')
            ->exists();
    }

    /**
     * One activity-log line per moderation action, causer = the acting admin.
     */
    private function audit(string $action, Model $subject, User $admin, ?string $reason = null): void
    {
        activity('feed_moderation')
            ->performedOn($subject)
            ->causedBy($admin)
            ->withProperties(array_filter(['reason' => $reason]))
            ->log($action);
    }

    /**
     * Tell the author their content was removed, and why.
     */
    private function notifyAuthor(Post|Comment $content, string $headline, string $reason): void
    {
        $author = $content->author()->withoutGlobalScopes()->first();

        if ($author instanceof Actor && method_exists($author, 'notify')) {
            $author->notify(new ContentRemoved([
                'message' => ':headline — :reason',
                'params' => ['headline' => $headline, 'reason' => $reason],
                'url' => null,
            ]));
        }
    }
}
