<?php

namespace App\Actions\Feed;

use App\Data\Feed\ParsedBodyData;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Validation\ValidationException;

/**
 * Persist a body's mentions from a parse result (ADR-F3). Idempotent: delete
 * everything and re-insert — on edit, offsets shift, so diffing is never
 * attempted. Enforces the per-type mention limits (feed-spec §7) as a
 * validation error naming the limit.
 */
class SyncMentions
{
    /**
     * Replace the mentionable's mention rows with the parse result's.
     */
    public function __invoke(Post|Comment $mentionable, ParsedBodyData $parsed): void
    {
        $limit = $mentionable instanceof Post
            ? (int) config('feed.limits.mentions_per_post')
            : (int) config('feed.limits.mentions_per_comment');

        if (count($parsed->mentions) > $limit) {
            throw ValidationException::withMessages([
                'body' => __('A :type may mention at most :limit accounts.', [
                    'type' => $mentionable instanceof Post ? __('post') : __('comment'),
                    'limit' => $limit,
                ]),
            ]);
        }

        $mentionable->mentions()->delete();

        foreach ($parsed->mentions as $mention) {
            $mentionable->mentions()->create([
                'mentioned_type' => $mention->mentionedType,
                'mentioned_id' => $mention->mentionedId,
                'start_offset' => $mention->startOffset,
                'length' => $mention->length,
            ]);
        }
    }
}
