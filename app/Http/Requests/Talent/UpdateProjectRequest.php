<?php

namespace App\Http\Requests\Talent;

use App\Models\Project;
use Illuminate\Support\Facades\Gate;

/**
 * Update a portfolio project. Same shape as the create request — a project is
 * edited whole, so `skill_ids` stays required (it can never drop to zero,
 * ADR-X).
 *
 * Co-ownership: ANY owner may edit (ProjectPolicy::update, checked here so a
 * stranger gets 403 before validation). The skill tags must belong to the
 * project's HOME owner (`talent_id`), not to whichever co-owner is editing —
 * the tags decide the home owner's profile tabs.
 */
class UpdateProjectRequest extends StoreProjectRequest
{
    /**
     * Only an owner (creator or accepted co-owner) may update.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && Gate::forUser($this->user('talent'))->allows('update', $project);
    }

    /**
     * The home owner's skills.
     *
     * @return list<int>
     */
    protected function taggableSkillIds(): array
    {
        $project = $this->route('project');

        return $project instanceof Project
            ? $project->talent->talentTypes()->pluck('talent_types.id')->map('intval')->all()
            : [];
    }
}
