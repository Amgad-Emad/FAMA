<?php

namespace App\Services;

use App\Mail\ProfileStepsCompleted;
use App\Models\Talent;
use Illuminate\Support\Facades\Mail;

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

    /** Every step done. */
    public function isComplete(Talent $talent): bool
    {
        return ! in_array(false, $this->steps($talent), true);
    }

    /**
     * Call after any write that can finish a step. The FIRST time all six are
     * done — while Fama has not checked the profile yet — stamp
     * `steps_completed_at` and email "you're done, we're checking it". The
     * stamp is set with a conditional UPDATE, so two racing requests can't
     * both send. Verified or Live profiles get nothing (nothing is pending).
     */
    public function notifyIfComplete(Talent $talent): void
    {
        if ($talent->steps_completed_at !== null || $talent->is_verified || $talent->is_published) {
            return;
        }

        $talent->unsetRelation('media')->unsetRelation('specialities');
        if (! $this->isComplete($talent)) {
            return;
        }

        $won = Talent::whereKey($talent->getKey())->whereNull('steps_completed_at')->update(['steps_completed_at' => now()]);
        $talent->steps_completed_at = now();

        if ($won === 1 && filled($talent->email)) {
            Mail::to($talent->email)->queue(new ProfileStepsCompleted(
                (string) ($talent->display_name ?: $talent->slug),
                route('talent.profile.edit'),
            ));
        }
    }
}
