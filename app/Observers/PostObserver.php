<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\CounterService;
use App\States\Post\Published;

/**
 * Keeps the author's posts_count in lockstep with the definition the nightly
 * reconcile uses (ADR-F6): posts_count = currently-published, non-deleted
 * posts. Entering the published state counts a post; leaving it (moderation
 * hide/remove) or soft-deleting uncounts it; restore re-counts it.
 */
class PostObserver
{
    public function __construct(private readonly CounterService $counters)
    {
    }

    /**
     * A post row landed already-published (seeders, direct creates).
     */
    public function created(Post $post): void
    {
        if ($this->isPublished($post)) {
            $this->counters->applyPostPublishDelta($post, 1);
        }
    }

    /**
     * Adjust the count when the post crosses the published boundary in either
     * direction (publish, moderation hide/remove, restore-to-published).
     */
    public function updated(Post $post): void
    {
        if (! $post->wasChanged('state')) {
            return;
        }

        $wasPublished = (string) $post->getRawOriginal('state') === Published::$name;
        $isPublished = $this->isPublished($post);

        if ($isPublished && ! $wasPublished) {
            $this->counters->applyPostPublishDelta($post, 1);
        } elseif (! $isPublished && $wasPublished) {
            $this->counters->applyPostPublishDelta($post, -1);
        }
    }

    /**
     * A post was deleted — uncount it if it was published.
     */
    public function deleted(Post $post): void
    {
        if ($this->isPublished($post)) {
            $this->counters->applyPostPublishDelta($post, -1);
        }
    }

    /**
     * A soft-deleted post was restored — re-count it if published.
     */
    public function restored(Post $post): void
    {
        if ($this->isPublished($post)) {
            $this->counters->applyPostPublishDelta($post, 1);
        }
    }

    /**
     * Whether the post's current state is Published.
     */
    private function isPublished(Post $post): bool
    {
        return $post->state instanceof Published;
    }
}
