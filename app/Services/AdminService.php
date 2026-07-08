<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Base for admin (platform-governance) services. Everything runs on the `admin`
 * log channel, is authorized against the acting admin's policy/permission, and
 * is recorded to the activity log with the admin as causer.
 */
abstract class AdminService extends Service
{
    protected string $logChannel = 'admin';

    /**
     * Authorize the acting admin for a policy ability on a subject (class-string
     * for class-level abilities, or a model instance). Throws
     * AuthorizationException when denied.
     *
     * @param  Model|class-string  $subject
     */
    protected function authorizeAdmin(User $admin, string $ability, Model|string $subject): void
    {
        Gate::forUser($admin)->authorize($ability, $subject);
    }

    /**
     * Authorize the acting admin for a bare spatie permission (no model subject —
     * e.g. media/settings oversight). Throws AuthorizationException when denied.
     */
    protected function authorizePermission(User $admin, string $permission): void
    {
        if (! $admin->can($permission)) {
            throw new AuthorizationException("This action requires the '{$permission}' permission.");
        }
    }

    /**
     * Record an admin-governed action to the activity log (subject + causer +
     * ad-hoc properties). Model change-tracking is handled separately by the
     * subject's LogsActivity trait where present.
     *
     * @param  array<string, mixed>  $properties
     */
    protected function record(User $admin, Model $subject, string $logName, string $description, array $properties = []): void
    {
        activity($logName)
            ->performedOn($subject)
            ->causedBy($admin)
            ->withProperties($properties)
            ->log($description);
    }
}
