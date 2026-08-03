<?php

namespace App\Policies;

use App\Feed\Actor;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Likes (feed-spec §2): any participating Actor may like/unlike posts and
 * comments; admins and guests may not (guests get the intent-replay path).
 */
class LikePolicy extends BasePolicy
{
    /**
     * Any Actor may toggle a like.
     */
    public function create(Authenticatable $user): bool
    {
        return $user instanceof Actor;
    }
}
