<?php

namespace App\Console\Commands;

use App\Models\FeedSeen;
use App\Models\LinkPreview;
use Illuminate\Console\Command;

/**
 * Nightly feed housekeeping (Prompt 5Q): drop feed_seen exposure marks older
 * than 60 days and failed link previews past their window — both pure
 * bookkeeping rows nothing references.
 */
class PruneFeed extends Command
{
    protected $signature = 'feed:prune';

    protected $description = 'Delete stale feed_seen rows and expired failed link previews';

    public function handle(): int
    {
        $seen = FeedSeen::query()->where('seen_at', '<', now()->subDays(60))->delete();

        $previews = LinkPreview::query()
            ->where('status', 'failed')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '<', now()))
            ->where('updated_at', '<', now()->subDays(7))
            ->whereDoesntHave('linkedUrls')
            ->delete();

        $this->info(sprintf('Pruned %d seen-marks and %d dead link previews.', $seen, $previews));

        return self::SUCCESS;
    }
}
