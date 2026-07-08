<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

/**
 * Admin capability policy for Campaign (Phase 3A). Gated on the acting admin's
 * spatie permission (`moderate-content`). Passing the model is optional (class-level checks).
 */
class CampaignPolicy
{
    public function oversee(User $user, ?Campaign $campaign = null): bool
    {
        return $user->can('moderate-content');
    }
}
