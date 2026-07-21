<?php

namespace App\Policies;

use App\Models\BrandReview;
use App\Models\User;

/**
 * Admin capability policy for BrandReview (Phase 3A). Gated on the acting admin's
 * spatie permission (`moderate-content`). Passing the model is optional (class-level checks).
 */
class BrandReviewPolicy
{
    public function moderate(User $user, ?BrandReview $review = null): bool
    {
        return $user->can('moderate-content');
    }
}
