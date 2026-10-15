<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Talent;

/**
 * Project co-ownership (2026-10-12): the owners are the creator plus every
 * accepted on-platform credit (Project::isOwnedBy is the single source).
 *
 *  - update — any owner edits the project and every write endpoint under it
 *             (cover, media, credits);
 *  - leave  — any owner may leave (the service decides HOW: decline the credit,
 *             or transfer the project when the creator leaves);
 *  - delete — owners only; the SOLE-owner rule is a domain rule enforced by
 *             TalentProjectService::delete (422 "Leave it instead"), so the UI
 *             gets a friendly message rather than a bare 403.
 */
class ProjectPolicy extends BasePolicy
{
    /**
     * Any owner — creator or accepted co-owner — may edit.
     */
    public function update(Talent $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    /**
     * Any owner may leave.
     */
    public function leave(Talent $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    /**
     * Only an owner may ask to delete (sole-ownership is checked by the service).
     */
    public function delete(Talent $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }
}
