<?php

namespace App\Listeners;

use App\Events\TalentProfileViewed;
use Illuminate\Support\Facades\DB;

/**
 * Rolls each profile view into its daily bucket (talent_profile_views) so the
 * dashboard can chart views over time. Race-safe: a single atomic
 * INSERT … ON DUPLICATE KEY UPDATE against the (talent_id, viewed_on) unique
 * key (MySQL-only, like the discovery functional index — ADR-6 precedent).
 * Lifetime `view_count` is still bumped by IncrementProfileViewCount.
 */
class RecordDailyProfileView
{
    public function handle(TalentProfileViewed $event): void
    {
        $now = now();

        DB::statement(
            'INSERT INTO talent_profile_views (talent_id, viewed_on, views, created_at, updated_at)
             VALUES (?, ?, 1, ?, ?)
             ON DUPLICATE KEY UPDATE views = views + 1, updated_at = VALUES(updated_at)',
            [$event->talent->getKey(), $now->toDateString(), $now, $now]
        );
    }
}
