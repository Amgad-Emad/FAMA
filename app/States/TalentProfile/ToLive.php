<?php

namespace App\States\TalentProfile;

use Spatie\ModelStates\DefaultTransition;

/**
 * Guarded publish transition: a profile can only go live once it has a display
 * name. The is_published flag + published_at stamp are applied as a side effect
 * by App\Listeners\SyncStateProjections when the StateChanged event fires.
 */
class ToLive extends DefaultTransition
{
    public function canTransition(): bool
    {
        return filled($this->model->display_name);
    }
}
