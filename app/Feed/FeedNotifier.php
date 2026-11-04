<?php

namespace App\Feed;

use App\Models\Comment;
use App\Models\Follow;
use App\Models\Post;
use App\Notifications\Feed;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * The single dispatch point for feed notifications (feed-spec §9). Owns every
 * rule so no action re-implements them:
 *  - never notify an actor about their own action;
 *  - repeated likes on one target within the aggregation window COLLAPSE into
 *    one record (actor count + latest actor, updated in place);
 *  - a mention suppresses the duplicate comment/reply notification for the
 *    same event;
 *  - every notification deep-links to its post/comment anchor;
 *  - a notification failure logs to the feed channel and NEVER blocks the
 *    originating write.
 */
class FeedNotifier
{
    /**
     * A post or comment was liked.
     */
    public function liked(Actor $liker, Post|Comment $target): void
    {
        $this->guard(function () use ($liker, $target): void {
            $author = $target->author()->withoutGlobalScopes()->first();

            if (! $author instanceof Actor || $this->same($author, $liker)) {
                return;
            }

            $isPost = $target instanceof Post;
            $class = $isPost ? Feed\PostLiked::class : Feed\CommentLiked::class;

            if ($this->aggregateLike($author, $class, $target, $liker)) {
                return;
            }

            $author->notify(new $class([
                'message' => $isPost ? ':name liked your post' : ':name liked your comment',
                'params' => ['name' => $liker->feedDisplayName()],
                'url' => $this->anchor($target),
                'actor' => $this->actorPayload($liker),
                'target' => $target->getMorphClass().':'.$target->getKey(),
                'actors_count' => 1,
            ]));
        });
    }

    /**
     * A comment landed on a post (suppressed for recipients the same comment
     * mentions — they get the mention notification instead).
     */
    public function commented(Actor $commenter, Comment $comment): void
    {
        $this->guard(function () use ($commenter, $comment): void {
            $post = $comment->post;
            $postAuthor = $post?->author()->withoutGlobalScopes()->first();
            $mentionedKeys = $this->mentionedKeys($comment);

            if ($postAuthor instanceof Actor
                && ! $this->same($postAuthor, $commenter)
                && ! in_array($this->key($postAuthor), $mentionedKeys, true)) {
                $postAuthor->notify(new Feed\PostCommented([
                    'message' => ':name commented on your post',
                    'params' => ['name' => $commenter->feedDisplayName()],
                    'url' => $this->anchor($comment),
                    'actor' => $this->actorPayload($commenter),
                    'excerpt' => $this->excerpt($comment),
                ]));
            }

            if ($comment->parent_id !== null) {
                $parentAuthor = $comment->parent?->author()->withoutGlobalScopes()->first();

                if ($parentAuthor instanceof Actor
                    && ! $this->same($parentAuthor, $commenter)
                    && ! ($postAuthor instanceof Actor && $this->same($parentAuthor, $postAuthor))
                    && ! in_array($this->key($parentAuthor), $mentionedKeys, true)) {
                    $parentAuthor->notify(new Feed\CommentReplied([
                        'message' => ':name replied to your comment',
                        'params' => ['name' => $commenter->feedDisplayName()],
                        'url' => $this->anchor($comment),
                        'actor' => $this->actorPayload($commenter),
                        'excerpt' => $this->excerpt($comment),
                    ]));
                }
            }

            $this->notifyMentions($commenter, $comment, inPost: false);
        });
    }

    /**
     * A post was published — notify its mentions.
     */
    public function posted(Actor $author, Post $post): void
    {
        $this->guard(fn () => $this->notifyMentions($author, $post, inPost: true));
    }

    /**
     * A NEW follow edge (unfollow/refollow inside the window won't re-notify:
     * the aggregate check treats an existing recent record as covered).
     */
    public function followed(Actor $follower, Actor $followed): void
    {
        $this->guard(function () use ($follower, $followed): void {
            if ($this->same($follower, $followed) || ! method_exists($followed, 'notify')) {
                return;
            }

            $recent = $followed->notifications()
                ->where('type', Feed\NewFollower::class)
                ->where('created_at', '>=', now()->subMinutes((int) config('feed.notifications.aggregate_minutes')))
                ->where('data->actor->key', $this->key($follower))
                ->exists();

            if ($recent) {
                return;
            }

            $followed->notify(new Feed\NewFollower([
                'message' => ':name started following you',
                'params' => ['name' => $follower->feedDisplayName()],
                'url' => $this->profileUrl($follower),
                'actor' => $this->actorPayload($follower),
            ]));
        });
    }

    /**
     * A Project share — the one follower fan-out (feed-spec Q5).
     */
    public function projectShared(Actor $author, Post $post): void
    {
        $this->guard(function () use ($author, $post): void {
            Follow::query()
                ->where('followable_type', $author->getMorphClass())
                ->where('followable_id', $author->getKey())
                ->with('follower')
                ->chunk(200, function ($edges) use ($author, $post): void {
                    foreach ($edges as $edge) {
                        $follower = $edge->follower;

                        if ($follower instanceof Actor && method_exists($follower, 'notify')) {
                            $follower->notify(new Feed\ProjectShared([
                                'message' => ':name shared a Project',
                                'params' => ['name' => $author->feedDisplayName()],
                                'url' => $this->anchor($post),
                                'actor' => $this->actorPayload($author),
                            ]));
                        }
                    }
                });
        });
    }

    /**
     * Collapse a repeat like into the existing unread record (update in
     * place: count + newest actor). True when aggregated.
     */
    private function aggregateLike(Actor $author, string $class, Post|Comment $target, Actor $liker): bool
    {
        /** @var DatabaseNotification|null $existing */
        $existing = $author->notifications()
            ->where('type', $class)
            ->where('created_at', '>=', now()->subMinutes((int) config('feed.notifications.aggregate_minutes')))
            ->where('data->target', $target->getMorphClass().':'.$target->getKey())
            ->first();

        if ($existing === null) {
            return false;
        }

        $data = $existing->data;
        $count = ($data['actors_count'] ?? 1) + 1;

        $existing->forceFill([
            'data' => array_merge($data, [
                'actors_count' => $count,
                'actor' => $this->actorPayload($liker),
                'message' => $target instanceof Post
                    ? ':name and :others others liked your post'
                    : ':name and :others others liked your comment',
                'params' => ['name' => $liker->feedDisplayName(), 'others' => $count - 1],
            ]),
            'read_at' => null,
            'updated_at' => now(),
        ])->save();

        return true;
    }

    /**
     * Mention notifications for a freshly-synced body.
     */
    private function notifyMentions(Actor $author, Post|Comment $body, bool $inPost): void
    {
        $class = $inPost ? Feed\MentionedInPost::class : Feed\MentionedInComment::class;
        $message = $inPost ? ':name mentioned you in a post' : ':name mentioned you in a comment';

        foreach ($body->mentions()->with('mentioned')->get() as $mention) {
            $mentioned = $mention->mentioned;

            if (! $mentioned instanceof Actor || $this->same($mentioned, $author) || ! method_exists($mentioned, 'notify')) {
                continue;
            }

            $mentioned->notify(new $class([
                'message' => $message,
                'params' => ['name' => $author->feedDisplayName()],
                'url' => $this->anchor($body),
                'actor' => $this->actorPayload($author),
                'excerpt' => $this->excerpt($body),
            ]));
        }
    }

    /**
     * One line of what was said (the notifications page shows it under the
     * message, prototype `ex`): the raw body squished to a single line and
     * capped — plain text, escaped at render. Null for an empty body.
     */
    private function excerpt(Post|Comment $body): ?string
    {
        $text = Str::of((string) $body->body)->squish()->limit((int) config('feed.notifications.excerpt_length', 140))->value();

        return $text !== '' ? $text : null;
    }

    /**
     * The deep link to a post, or a comment's anchor on its post.
     */
    private function anchor(Post|Comment $target): string
    {
        return $target instanceof Post
            ? route('posts.show', $target)
            : route('posts.show', $target->post_id).'#comment-'.$target->getKey();
    }

    /**
     * An actor's public profile URL.
     */
    private function profileUrl(Actor $actor): string
    {
        return $actor->getMorphClass() === 'brand'
            ? route('brand.public', ['brand' => $actor->feedUsername()])
            : route('talent.public', ['slug' => $actor->feedUsername()]);
    }

    /**
     * The compact actor payload stored on every notification.
     *
     * @return array<string, mixed>
     */
    private function actorPayload(Actor $actor): array
    {
        return [
            'key' => $this->key($actor),
            'type' => $actor->getMorphClass(),
            'id' => $actor->getKey(),
            'username' => $actor->feedUsername(),
            'name' => $actor->feedDisplayName(),
        ];
    }

    private function key(Actor $actor): string
    {
        return $actor->getMorphClass().':'.$actor->getKey();
    }

    /**
     * "type:id" keys of every actor the body mentions — the suppression set
     * for comment/reply notifications (feed-spec §9).
     *
     * @return list<string>
     */
    private function mentionedKeys(Comment $comment): array
    {
        return $comment->mentions()
            ->get(['mentioned_type', 'mentioned_id'])
            ->map(fn ($mention): string => $mention->mentioned_type.':'.$mention->mentioned_id)
            ->all();
    }

    private function same(Actor $a, Actor $b): bool
    {
        return $this->key($a) === $this->key($b);
    }

    /**
     * Notification failures log and never block the originating write.
     */
    private function guard(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            Log::channel('feed')->warning('Feed notification dispatch failed', [
                'exception' => $e,
            ]);
        }
    }
}
