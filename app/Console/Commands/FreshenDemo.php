<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Slide the demo feed forward in time so an aging demo/dev database ranks again.
 *
 * The feed only ranks posts inside its windows (config feed.ranking: tier1/tier2
 * 30 days), and seeded content is dated relative to SEED time — a dev DB seeded
 * weeks ago shows an empty /feed. This shifts every feed timestamp by the same
 * delta so the newest post lands ~1 hour ago, keeping relative order intact, then
 * flushes the cache (frozen candidate lists, suggestion pools). Never shifts
 * backwards. Refuses to run in production without --force.
 */
class FreshenDemo extends Command
{
    protected $signature = 'demo:freshen {--force : Run even in production}';

    protected $description = 'Shift demo feed timestamps forward so the feed has recent content';

    /** Table → timestamp columns moved by the delta. */
    private const COLUMNS = [
        'posts' => ['published_at', 'created_at', 'updated_at'],
        'comments' => ['created_at', 'updated_at'],
        'likes' => ['created_at', 'updated_at'],
        'bookmarks' => ['created_at'],
    ];

    /**
     * Compute the delta from the newest published post and apply it to every feed table.
     */
    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Refusing to rewrite timestamps in production (use --force).');

            return self::FAILURE;
        }

        $newest = DB::table('posts')->whereNotNull('published_at')->max('published_at');

        if (! $newest) {
            $this->info('No published posts — nothing to freshen.');

            return self::SUCCESS;
        }

        $seconds = now()->subHour()->getTimestamp() - strtotime($newest);

        if ($seconds <= 0) {
            $this->info('The feed is already fresh.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($seconds): void {
            foreach (self::COLUMNS as $table => $columns) {
                $set = collect($columns)->mapWithKeys(fn ($c) => [$c => DB::raw("DATE_ADD(`{$c}`, INTERVAL {$seconds} SECOND)")])->all();
                DB::table($table)->update($set);
            }
            // talents.last_posted_at drives "Posted N days ago" / "Quiet since".
            DB::table('talents')->whereNotNull('last_posted_at')
                ->update(['last_posted_at' => DB::raw("DATE_ADD(`last_posted_at`, INTERVAL {$seconds} SECOND)")]);
        });

        Cache::flush();

        $this->info(sprintf('Shifted the feed forward by %.1f days.', $seconds / 86400));

        return self::SUCCESS;
    }
}
