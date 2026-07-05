<?php

namespace App\Policies;

use App\Models\AgencyAffiliation;
use App\Models\Talent;

/**
 * A talent may only manage its own agency affiliations.
 */
class AgencyAffiliationPolicy extends BasePolicy
{
    public function update(Talent $user, AgencyAffiliation $affiliation): bool
    {
        return $this->owns($user, $affiliation);
    }

    public function delete(Talent $user, AgencyAffiliation $affiliation): bool
    {
        return $this->owns($user, $affiliation);
    }
}
