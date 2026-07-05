<?php

namespace App\Policies;

use App\Models\ProfileBlock;
use App\Models\Talent;

/**
 * A talent may only manage blocks on its own profile.
 */
class ProfileBlockPolicy extends BasePolicy
{
    public function update(Talent $user, ProfileBlock $block): bool
    {
        return $this->owns($user, $block);
    }

    public function delete(Talent $user, ProfileBlock $block): bool
    {
        return $this->owns($user, $block);
    }
}
