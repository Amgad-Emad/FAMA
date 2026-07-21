<?php

namespace App\Policies;

use App\Models\Talent;
use App\Models\User;

/**
 * A talent may only manage its own profile; an admin with `moderate-content`
 * may moderate any talent (suspend / unpublish / soft-delete).
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

    public function moderate(User $user, ?Talent $talent = null): bool
    {
        return $user->can('moderate-content');
    }
}
