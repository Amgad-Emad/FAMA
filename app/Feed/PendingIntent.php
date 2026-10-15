<?php

namespace App\Feed;

use App\Actions\Feed\CreateComment;
use App\Actions\Feed\ToggleFollow;
use App\Actions\Feed\ToggleLike;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Talent;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Captures a guest's attempted write and replays it exactly once after
 * authentication (ADR-F8, feed-spec §11). The intent — {action, target_type,
 * target_id, payload} — is stored in the session by the require.actor
 * middleware when it rejects a guest; the Login event listener calls replay().
 *
 * Replay is best-effort: the intent is cleared BEFORE execution (exactly-once
 * beats at-least-once here — a failed replay must never loop), and any failure
 * logs to the feed channel without disturbing the login.
 */
class PendingIntent
{
    private const SESSION_KEY = 'feed.pending_intent';

    /**
     * The actions a stored intent may replay. Anything else is dropped.
     */
    private const ACTIONS = ['like', 'follow', 'comment', 'bookmark'];

    /**
     * Store a guest's attempted action for post-auth replay.
     *
     * @param  array{action: string, target_type: string, target_id: int|string, payload?: array<string, mixed>}  $intent
     */
    public function capture(array $intent): void
    {
        if (! in_array($intent['action'] ?? null, self::ACTIONS, true)) {
            return;
        }

        session()->put(self::SESSION_KEY, $intent);
    }

    /**
     * The stored intent, if any (read-only — replay() consumes it).
     *
     * @return array{action: string, target_type: string, target_id: int|string, payload?: array<string, mixed>}|null
     */
    public function peek(): ?array
    {
        return session()->get(self::SESSION_KEY);
    }

    /**
     * The login page's "finish what you started" line for the stored intent
     * (prototype login.html `next` note), or null when nothing is pending.
     * A follow names its target talent; the other actions name the post.
     */
    public function loginNote(): ?string
    {
        $intent = $this->peek();

        if ($intent === null) {
            return null;
        }

        $back = __("We'll finish it as soon as you're in.");

        if ($intent['action'] === 'follow' && $intent['target_type'] === 'talent') {
            $name = Talent::query()->find($intent['target_id'])?->feedDisplayName();

            if ($name !== null) {
                return __('Log in to finish following :name.', ['name' => $name]).' '.$back;
            }
        }

        return match ($intent['action']) {
            'like' => __('Log in to finish liking this.'),
            'comment' => __('Log in to post your comment.'),
            'bookmark' => __('Log in to finish saving this post.'),
            default => __('Log in to finish following them.'),
        }.' '.$back;
    }

    /**
     * Drop the stored intent without replaying.
     */
    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Replay the stored intent as the freshly-authenticated actor, exactly
     * once. Failures log and never surface — the login must succeed regardless.
     */
    public function replay(Actor $actor): void
    {
        $intent = $this->peek();

        if ($intent === null) {
            return;
        }

        $this->clear();

        try {
            $this->perform($actor, $intent);
        } catch (Throwable $e) {
            Log::channel('feed')->warning('Pending intent replay failed', [
                'actor' => $actor->getMorphClass().':'.$actor->getKey(),
                'intent' => $intent,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Execute one intent through the same Actions the live endpoints use.
     *
     * @param  array{action: string, target_type: string, target_id: int|string, payload?: array<string, mixed>}  $intent
     */
    private function perform(Actor $actor, array $intent): void
    {
        match ($intent['action']) {
            'like' => $this->replayLike($actor, $intent),
            'follow' => $this->replayFollow($actor, $intent),
            'comment' => $this->replayComment($actor, $intent),
            'bookmark' => $this->replayBookmark($actor, $intent),
            default => null,
        };
    }

    /**
     * Replay a like on a post or comment; only ever likes (never un-likes an
     * existing like the actor already had).
     */
    private function replayLike(Actor $actor, array $intent): void
    {
        $target = $this->resolveTarget($intent['target_type'], $intent['target_id']);

        if ($target instanceof Post || $target instanceof Comment) {
            app(ToggleLike::class)($actor, $target, onlyLike: true);
        }
    }

    /**
     * Replay a follow of a talent; only ever follows.
     */
    private function replayFollow(Actor $actor, array $intent): void
    {
        if ($intent['target_type'] !== 'talent') {
            return;
        }

        $talent = Talent::query()->find($intent['target_id']);

        if ($talent !== null) {
            app(ToggleFollow::class)($actor, $talent, onlyFollow: true);
        }
    }

    /**
     * Replay a comment (the guest's typed body travels in the payload).
     */
    private function replayComment(Actor $actor, array $intent): void
    {
        $body = trim((string) ($intent['payload']['body'] ?? ''));

        if ($body === '' || $intent['target_type'] !== 'post') {
            return;
        }

        $post = Post::query()->find($intent['target_id']);

        if ($post !== null) {
            $parentId = $intent['payload']['parent_id'] ?? null;
            $parent = $parentId !== null ? Comment::query()->find($parentId) : null;

            app(CreateComment::class)($actor, $post, $body, $parent);
        }
    }

    /**
     * Replay a save; only ever saves.
     */
    private function replayBookmark(Actor $actor, array $intent): void
    {
        if ($intent['target_type'] !== 'post') {
            return;
        }

        $post = Post::query()->find($intent['target_id']);

        if ($post !== null) {
            app(\App\Actions\Feed\ToggleBookmark::class)($actor, $post, onlySave: true);
        }
    }

    /**
     * Resolve a morph-aliased target row.
     */
    private function resolveTarget(string $type, int|string $id): ?object
    {
        return match ($type) {
            'post' => Post::query()->find($id),
            'comment' => Comment::query()->find($id),
            default => null,
        };
    }
}
