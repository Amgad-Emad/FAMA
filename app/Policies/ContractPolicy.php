<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

/**
 * Admin capability policy for Contract (Phase 3A). Gated on the acting admin's
 * spatie permission (`intervene-contracts`). Passing the model is optional (class-level checks).
 */
class ContractPolicy
{
    public function intervene(User $user, ?Contract $contract = null): bool
    {
        return $user->can('intervene-contracts');
    }
}
