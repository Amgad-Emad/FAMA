<?php

namespace App\Actions\Feed;

use App\Feed\Actor;
use App\Models\Comment;
use App\Models\Post;
use App\Services\RichTextParser;
use App\States\Comment\Published;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Create a comment or reply on a post (feed-spec §3, ADR-F7). Depth is capped
 * at two: replying to a reply re-attaches to the reply's parent and prefixes
 * an @mention of the reply's author. Counters move via CommentObserver, and
 * mentions/links parse-and-sync (5D), all inside this transaction (ADR-F6).
 * Notifications join in 5J.
 */
class CreateComment
{
    public function __construct(
        private readonly RichTextParser $parser,
        private readonly SyncMentions $syncMentions,
        private readonly SyncLinks $syncLinks,
        private readonly \App\Feed\FeedNotifier $notifier,
    ) {
    }
    /**
     * Persist the comment (optionally as a reply to $parent) and return it.
     */
    public function __invoke(Actor $author, Post $post, string $body, ?Comment $parent = null): Comment
    {
        $body = trim($body);

        if ($body === '') {
            throw new InvalidArgumentException(__('A comment needs a body.'));
        }

        if (mb_strlen($body) > (int) config('feed.limits.body_max_chars')) {
            throw new InvalidArgumentException(
                __('A comment may be at most :max characters.', ['max' => config('feed.limits.body_max_chars')])
            );
        }

        $comment = DB::transaction(function () use ($author, $post, $body, $parent): Comment {
            // Depth cap (ADR-F7): a reply to a reply attaches to the same
            // parent, @-mentioning the reply's author for context.
            if ($parent !== null && $parent->isReply()) {
                $replyAuthor = $parent->author()->withoutGlobalScopes()->first();

                if ($replyAuthor instanceof Actor) {
                    $handle = '@'.$replyAuthor->feedUsername().' ';

                    if (! str_starts_with($body, $handle)) {
                        $body = $handle.$body;
                    }
                }

                $parent = $parent->parent;
            }

            if ($parent !== null && $parent->post_id !== $post->id) {
                throw new InvalidArgumentException(__('The reply does not belong to this post.'));
            }

            $comment = Comment::query()->create([
                'post_id' => $post->id,
                'parent_id' => $parent?->id,
                'author_type' => $author->getMorphClass(),
                'author_id' => $author->getKey(),
                'body' => $body,
                'state' => Published::$name,
            ]);

            $parsed = $this->parser->parse($body);
            ($this->syncMentions)($comment, $parsed);
            ($this->syncLinks)($comment, $parsed);

            return $comment;
        });

        // Post-commit: never notify inside the transaction (feed-spec §9).
        $this->notifier->commented($author, $comment);

        return $comment;
    }
}
