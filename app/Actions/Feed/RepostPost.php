<?php

namespace App\Actions\Feed;

use App\Feed\Actor;
use App\Feed\FeedNotifier;
use App\Models\Post;
use App\Services\RichTextParser;
use App\States\Post\Draft;
use App\States\Post\Published;
use Illuminate\Support\Facades\DB;

/**
 * Share a post to your own feed (ADR-AM, prototype "Share to your feed"): a new
 * `repost` post pointing at the original, with your own words as its body
 * (optional; mentions and links parse like any post). Sharing a repost shares
 * ITS original — a feed never shows a repost of a repost.
 */
class RepostPost
{
    public function __construct(
        private readonly RichTextParser $parser,
        private readonly SyncMentions $syncMentions,
        private readonly SyncLinks $syncLinks,
        private readonly FeedNotifier $notifier,
    ) {}

    /**
     * Create the repost and publish it.
     */
    public function __invoke(Actor $author, Post $original, ?string $body): Post
    {
        $target = $original->post_type === 'repost' && $original->reposted_post_id
            ? Post::query()->findOrFail($original->reposted_post_id)
            : $original;
        $body = $body !== null && trim($body) !== '' ? trim($body) : null;

        $post = DB::transaction(function () use ($author, $target, $body): Post {
            $post = Post::query()->create([
                'author_type' => $author->getMorphClass(),
                'author_id' => $author->getKey(),
                'post_type' => 'repost',
                'reposted_post_id' => $target->getKey(),
                'body' => $body,
                'state' => Draft::$name,
            ]);

            if ($body !== null) {
                $parsed = $this->parser->parse($body);
                ($this->syncMentions)($post, $parsed);
                ($this->syncLinks)($post, $parsed);
            }

            $post->forceFill(['published_at' => now()])->save();
            $post->state->transitionTo(Published::class);

            return $post->refresh()->load(['mentions', 'linkedUrls.preview', 'repostOf']);
        });

        // Post-commit mention notifications, as for any post.
        $this->notifier->posted($author, $post);

        return $post;
    }
}
