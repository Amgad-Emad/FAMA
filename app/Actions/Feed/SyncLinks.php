<?php

namespace App\Actions\Feed;

use App\Data\Feed\ParsedBodyData;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Validation\ValidationException;

/**
 * Persist a body's URLs from a parse result (ADR-F4). Idempotent delete-and-
 * re-insert (offsets shift on edit); each URL joins the deduplicated
 * link_previews row via ResolveLinkPreview. Enforces the URL limit
 * (feed-spec §8) as a validation error naming the limit.
 */
class SyncLinks
{
    public function __construct(private readonly ResolveLinkPreview $resolvePreview)
    {
    }

    /**
     * Replace the linkable's linked_urls rows with the parse result's.
     */
    public function __invoke(Post|Comment $linkable, ParsedBodyData $parsed): void
    {
        $limit = (int) config('feed.limits.urls_per_body');

        if (count($parsed->urls) > $limit) {
            throw ValidationException::withMessages([
                'body' => __('A body may contain at most :limit links.', ['limit' => $limit]),
            ]);
        }

        $linkable->linkedUrls()->delete();

        foreach ($parsed->urls as $position => $url) {
            $preview = ($this->resolvePreview)($url->url);

            $linkable->linkedUrls()->create([
                'link_preview_id' => $preview->id,
                'position' => $position,
            ]);
        }
    }
}
