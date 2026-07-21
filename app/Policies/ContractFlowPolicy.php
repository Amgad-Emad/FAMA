<?php

namespace App\Policies;

use App\Models\ContractFlow;
use App\Models\User;

/**
 * Admin capability policy for ContractFlow (Phase 3A). Gated on the acting admin's
 * spatie permission (`manage-flows`). Passing the model is optional (class-level checks).
 */
class ContractFlowPolicy
{
    public function manage(User $user, ?ContractFlow $flow = null): bool
    {
        return $user->can('manage-flows');
    }
}
