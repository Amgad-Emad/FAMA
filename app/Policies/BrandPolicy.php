<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

/**
 * Admin capability policy for Brand (Phase 3A). Gated on the acting admin's
 * spatie permission (`moderate-content`). Passing the model is optional (class-level checks).
 */
class BrandPolicy
{
    public function moderate(User $user, ?Brand $brand = null): bool
    {
        return $user->can('moderate-content');
    }
}
