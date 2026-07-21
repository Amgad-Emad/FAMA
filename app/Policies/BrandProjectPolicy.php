<?php

namespace App\Policies;

use App\Models\BrandProject;
use App\Models\User;

/**
 * Admin capability policy for BrandProject (Phase 3A). Gated on the acting admin's
 * spatie permission (`moderate-content`). Passing the model is optional (class-level checks).
 */
class BrandProjectPolicy
{
    public function oversee(User $user, ?BrandProject $project = null): bool
    {
        return $user->can('moderate-content');
    }
}
