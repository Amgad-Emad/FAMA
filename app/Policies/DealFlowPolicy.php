<?php

namespace App\Policies;

use App\Models\DealFlow;
use App\Models\User;

/**
 * Admin capability policy for DealFlow (Phase 3A). Gated on the acting admin's
 * spatie permission (`manage-flows`). Passing the model is optional (class-level checks).
 */
class DealFlowPolicy
{
    public function manage(User $user, ?DealFlow $flow = null): bool
    {
        return $user->can('manage-flows');
    }
}
