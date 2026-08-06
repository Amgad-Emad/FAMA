<?php

namespace App\Policies;

use App\Models\ContractTemplate;
use App\Models\User;

/**
 * Admin capability policy for ContractTemplate (ADR-V). Authoring contract
 * templates rides on the same permission as contract flows (`manage-flows`) —
 * they are two halves of the same contract-authoring surface. Passing the model
 * is optional (class-level checks).
 */
class ContractTemplatePolicy
{
    public function manage(User $user, ?ContractTemplate $template = null): bool
    {
        return $user->can('manage-flows');
    }
}
