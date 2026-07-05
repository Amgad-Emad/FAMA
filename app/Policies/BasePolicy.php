<?php

namespace App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Base authorization policy convention for Fama.
 *
 * Policies gate own-resource edits and admin intervention/override. The common
 * cases are centralised here:
 *  - `owns()` — does this authenticated entity own the given model (matching
 *    `talent_id` / `brand_id` foreign keys or the model's own id)?
 *
 * Concrete policies extend this class and implement the ability methods
 * (view/create/update/delete/...). Admin override is applied per-policy via a
 * `before()` hook once the admin role/permission model lands.
 */
abstract class BasePolicy
{
    /**
     * Determine whether the authenticated entity owns the model. Checks the
     * conventional foreign key for the entity's morph class, then falls back to
     * matching primary keys when the model *is* the entity (e.g. a talent
     * editing its own profile).
     */
    protected function owns(Authenticatable $user, Model $model): bool
    {
        $foreignKey = $user->getForeignKey(); // e.g. talent_id, brand_id, user_id

        if ($model->getAttribute($foreignKey) !== null) {
            return (string) $model->getAttribute($foreignKey) === (string) $user->getAuthIdentifier();
        }

        return $model::class === $user::class
            && (string) $model->getKey() === (string) $user->getAuthIdentifier();
    }
}
