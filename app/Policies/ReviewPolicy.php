<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\Talent;
use App\Models\User;

/**
 * A talent may only moderate reviews left on its own profile; an admin with
 * `moderate-content` may moderate any review (approve / reject, incl. batch).
 */
class ReviewPolicy extends BasePolicy
{
    public function update(Talent $user, Review $review): bool
    {
        return $this->owns($user, $review);
    }

    public function delete(Talent $user, Review $review): bool
    {
        return $this->owns($user, $review);
    }

    public function moderate(User $user, ?Review $review = null): bool
    {
        return $user->can('moderate-content');
    }
}
