<?php

namespace App\States\Brand;

use Spatie\ModelStates\DefaultTransition;

/**
 * Guarded publish transition: a brand can only go public once an admin has
 * verified it (`is_verified`). Onboarding completeness is enforced separately at
 * the self-serve publish endpoint; this guard is the hard floor that keeps any
 * unverified brand off the public surfaces regardless of entry point. The
 * is_published flag is a synced projection (App\Listeners\SyncStateProjections).
 */
class ToPublished extends DefaultTransition
{
    public function canTransition(): bool
    {
        return (bool) $this->model->is_verified;
    }
}
