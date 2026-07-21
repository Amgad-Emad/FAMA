<?php

namespace App\Policies;

use App\Models\TalentType;
use App\Models\User;

/**
 * Admin capability policy for TalentType (Phase 3A). Gated on the acting admin's
 * spatie permission (`manage-flows`). Passing the model is optional (class-level checks).
 */
class TalentTypePolicy
{
    public function manage(User $user, ?TalentType $type = null): bool
    {
        return $user->can('manage-flows');
    }
}
