<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

/**
 * Admin capability policy for Deal (Phase 3A). Gated on the acting admin's
 * spatie permission (`intervene-deals`). Passing the model is optional (class-level checks).
 */
class DealPolicy
{
    public function intervene(User $user, ?Deal $deal = null): bool
    {
        return $user->can('intervene-deals');
    }
}
