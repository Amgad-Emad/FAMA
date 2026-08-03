<?php

namespace App\Policies;

use App\Feed\Actor;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Follows (feed-spec §2): any participating Actor may follow, except itself.
 * The edge rules (talent→talent, brand→talent, config-gated talent→brand)
 * are domain validation in ToggleFollow; the policy owns identity questions.
 */
class FollowPolicy extends BasePolicy
{
    /**
     * Any Actor may follow another party — self-follow is explicitly denied.
     */
    public function create(Authenticatable $user, ?Actor $followable = null): bool
    {
        if (! $user instanceof Actor) {
            return false;
        }

        if ($followable !== null
            && $followable->getMorphClass() === $user->getMorphClass()
            && (string) $followable->getKey() === (string) $user->getAuthIdentifier()) {
            return false;
        }

        return true;
    }
}
