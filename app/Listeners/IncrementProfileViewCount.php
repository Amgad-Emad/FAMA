<?php

namespace App\Listeners;

use App\Events\TalentProfileViewed;

/**
 * Increments the talent's view_count when its profile is viewed (talent-spec:
 * "each view bumps view_count").
 */
class IncrementProfileViewCount
{
    public function handle(TalentProfileViewed $event): void
    {
        $event->talent->increment('view_count');
    }
}
