<?php

namespace App\Policies;

use App\Feed\Actor;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Bookmarks (5X, ADR-F9): any participating Actor may save; collections are
 * PRIVATE — every read endpoint scopes to the current actor, so there is no
 * view-other ability to model. The posts-only type gate lives in
 * ToggleBookmark with a clear message.
 */
class BookmarkPolicy extends BasePolicy
{
    /**
     * Any Actor may toggle a save.
     */
    public function create(Authenticatable $user): bool
    {
        return $user instanceof Actor;
    }
}
