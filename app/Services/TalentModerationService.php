<?php

namespace App\Services;

use App\Models\Talent;
use App\Models\User;
use App\States\TalentProfile\Live;
use App\States\TalentProfile\Suspended;
use App\States\TalentProfile\Unpublished;

/**
 * Admin moderation of talent profiles (Phase 3A). Suspend / unpublish / soft-delete
 * (and restore). Gated on `moderate-content` (TalentPolicy@moderate); each action
 * is transactional and recorded to the activity log with the admin as causer.
 */
class TalentModerationService extends AdminService
{
    public function suspend(User $admin, Talent $talent, ?string $reason = null): Talent
    {
        $this->authorizeAdmin($admin, 'moderate', $talent);

        return $this->runInTransaction(function () use ($admin, $talent, $reason): Talent {
            if ($talent->status->canTransitionTo(Suspended::class)) {
                $talent->status->transitionTo(Suspended::class);
            }
            $this->record($admin, $talent, 'moderation', 'talent.suspended', ['reason' => $reason]);

            return $talent->refresh();
        }, ['talent_id' => $talent->getKey()]);
    }

    public function unpublish(User $admin, Talent $talent, ?string $reason = null): Talent
    {
        $this->authorizeAdmin($admin, 'moderate', $talent);

        return $this->runInTransaction(function () use ($admin, $talent, $reason): Talent {
            if ($talent->status->canTransitionTo(Unpublished::class)) {
                $talent->status->transitionTo(Unpublished::class);
            }
            $this->record($admin, $talent, 'moderation', 'talent.unpublished', ['reason' => $reason]);

            return $talent->refresh();
        }, ['talent_id' => $talent->getKey()]);
    }

    /**
     * Reverse of unpublish: put a hidden profile back Live (the `ToLive` guard
     * still requires a display name). The toggle counterpart of unpublish().
     */
    public function publish(User $admin, Talent $talent, ?string $reason = null): Talent
    {
        $this->authorizeAdmin($admin, 'moderate', $talent);

        return $this->runInTransaction(function () use ($admin, $talent, $reason): Talent {
            if ($talent->status->canTransitionTo(Live::class)) {
                $talent->status->transitionTo(Live::class);
            }
            $this->record($admin, $talent, 'moderation', 'talent.published', ['reason' => $reason]);

            return $talent->refresh();
        }, ['talent_id' => $talent->getKey()]);
    }

    /**
     * Reverse of suspend: lift a suspension back to the hidden Unpublished state
     * (the admin can then publish). The toggle counterpart of suspend().
     */
    public function unsuspend(User $admin, Talent $talent, ?string $reason = null): Talent
    {
        $this->authorizeAdmin($admin, 'moderate', $talent);

        return $this->runInTransaction(function () use ($admin, $talent, $reason): Talent {
            if ($talent->status->canTransitionTo(Unpublished::class)) {
                $talent->status->transitionTo(Unpublished::class);
            }
            $this->record($admin, $talent, 'moderation', 'talent.unsuspended', ['reason' => $reason]);

            return $talent->refresh();
        }, ['talent_id' => $talent->getKey()]);
    }

    public function softDelete(User $admin, Talent $talent, ?string $reason = null): void
    {
        $this->authorizeAdmin($admin, 'moderate', $talent);

        $this->runInTransaction(function () use ($admin, $talent, $reason): void {
            $this->record($admin, $talent, 'moderation', 'talent.soft_deleted', ['reason' => $reason]);
            $talent->delete();
        }, ['talent_id' => $talent->getKey()]);
    }

    public function restore(User $admin, Talent $talent): Talent
    {
        $this->authorizeAdmin($admin, 'moderate', $talent);

        return $this->runInTransaction(function () use ($admin, $talent): Talent {
            $talent->restore();
            $this->record($admin, $talent, 'moderation', 'talent.restored');

            return $talent->refresh();
        }, ['talent_id' => $talent->getKey()]);
    }
}
