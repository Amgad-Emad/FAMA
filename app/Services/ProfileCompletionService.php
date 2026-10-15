<?php

namespace App\Services;

use App\Models\Talent;

/**
 * The steps between a new profile and a complete one — the checklist the
 * Profile structure page shows while the profile is Private (the fama-ui
 * "Creatives Index" gate). Read-only: it reports what is done, it never
 * blocks a write. Publishing is still decided by TalentProfileService
 * (verification + display name); these steps are guidance on top of it.
 */
class ProfileCompletionService extends Service
{
    /** The step keys, in the order the checklist shows them. */
    public const STEPS = ['photo', 'name', 'place', 'bio', 'skill', 'project'];

    /**
     * Each step with whether the talent has done it.
     *
     *  photo   — an uploaded avatar (the initials fallback does not count)
     *  name    — a display name
     *  place   — both a base city and a base country
     *  bio     — an English bio
     *  skill   — at least one skill with at least one speciality picked
     *  project — at least one project of their own
     *
     * @return array<string, bool>
     */
    public function steps(Talent $talent): array
    {
        $talent->loadMissing(['media', 'specialities']);

        return [
            'photo' => $talent->getFirstMedia('avatar') !== null,
            'name' => filled($talent->display_name),
            'place' => filled($talent->base_city) && filled($talent->base_country),
            'bio' => filled($talent->getTranslation('bio', 'en', false)),
            'skill' => $talent->specialities->isNotEmpty(),
            'project' => $talent->projects()->exists(),
        ];
    }
}
