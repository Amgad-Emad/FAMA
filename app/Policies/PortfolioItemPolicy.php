<?php

namespace App\Policies;

use App\Models\PortfolioItem;
use App\Models\Talent;

/**
 * A talent may only manage its own portfolio items.
 */
class PortfolioItemPolicy extends BasePolicy
{
    public function update(Talent $user, PortfolioItem $item): bool
    {
        return $this->owns($user, $item);
    }

    public function delete(Talent $user, PortfolioItem $item): bool
    {
        return $this->owns($user, $item);
    }
}
