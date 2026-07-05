<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\Talent;

/**
 * A talent may only moderate reviews left on its own profile.
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
}
