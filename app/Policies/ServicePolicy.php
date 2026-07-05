<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\Talent;

/**
 * A talent may only manage its own rate-card services.
 */
class ServicePolicy extends BasePolicy
{
    public function update(Talent $user, Service $service): bool
    {
        return $this->owns($user, $service);
    }

    public function delete(Talent $user, Service $service): bool
    {
        return $this->owns($user, $service);
    }
}
