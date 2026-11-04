<?php

namespace App\States\TalentProfile;

use Spatie\ModelStates\DefaultTransition;

/**
 * Guarded publish transition — the hard backstop: never Live without a display
 * name. Admin verification is NO LONGER a gate (ADR-AC, 2026-11-04, supersedes
 * ADR-U's talent gate). The full Go Live rule (name + skill/speciality + city)
 * is enforced where the talent goes Live — SyncGoLiveState and
 * TalentProfileService::publish — so an admin's Publish stays an override. The
 * is_published flag + published_at stamp are applied as a side effect by
 * App\Listeners\SyncStateProjections when the StateChanged event fires.
 */
class ToLive extends DefaultTransition
{
    /** A display name is the only hard requirement for any Live transition. */
    public function canTransition(): bool
    {
        return filled($this->model->display_name);
    }
}
