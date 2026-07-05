<?php

namespace App\Policies;

use App\Models\Talent;

/**
 * A talent may only manage its own profile.
 */
class TalentPolicy extends BasePolicy
{
    public function update(Talent $user, Talent $talent): bool
    {
        return $this->owns($user, $talent);
    }

    public function delete(Talent $user, Talent $talent): bool
    {
        return $this->owns($user, $talent);
    }
}
