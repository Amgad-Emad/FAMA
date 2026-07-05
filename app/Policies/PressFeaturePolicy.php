<?php

namespace App\Policies;

use App\Models\PressFeature;
use App\Models\Talent;

/**
 * A talent may only manage its own press features.
 */
class PressFeaturePolicy extends BasePolicy
{
    public function update(Talent $user, PressFeature $feature): bool
    {
        return $this->owns($user, $feature);
    }

    public function delete(Talent $user, PressFeature $feature): bool
    {
        return $this->owns($user, $feature);
    }
}
