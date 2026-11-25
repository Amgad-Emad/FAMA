<?php

namespace App\Actions\Talent;

use App\Models\Talent;
use App\Services\ProfileCompletionService;
use App\States\TalentProfile\Created;
use App\States\TalentProfile\Draft;
use App\States\TalentProfile\Live;
use App\States\TalentProfile\Unpublished;

/**
 * Keeps a talent's Draft / Private state in step with the Go Live steps
 * (ADR-AQ, 2026-11-23 — supersedes ADR-AC's auto-Live). The profile path the
 * talent sees is Draft → Private → Live:
 *
 *  - ready + Created/Draft                 → Private (Unpublished). Going Live
 *                                            is the talent's own call (the Go
 *                                            Live button → TalentProfileService::publish).
 *  - not ready + Private/Live + WAS ready  → Draft (this write broke a step;
 *                                            the editor warns before such a save)
 *  - Suspended / Archived                  → untouched: Fama's hold is never
 *                                            undone by a save.
 *
 * Going OUT is edge-triggered (`$wasReady === true`): a profile that was Live
 * or Private before a step existed (e.g. no speciality) is grandfathered, never
 * silently pulled down by an unrelated edit. Callers run it in a transaction
 * (ProfileCompletionService::syncAfterWrite).
 */
class SyncGoLiveState
{
    /** @param  ProfileCompletionService  $completion  the Go Live rule (single home) */
    public function __construct(private readonly ProfileCompletionService $completion) {}

    /**
     * Apply the rule to the talent's SAVED data. `$wasReady` = canGoLive()
     * captured before the write (null when unknown — never takes a profile out).
     */
    public function __invoke(Talent $talent, ?bool $wasReady = null): Talent
    {
        $talent->unsetRelation('specialities');
        $ready = $this->completion->canGoLive($talent);

        if ($ready && ($talent->status instanceof Created || $talent->status instanceof Draft)) {
            if ($talent->status instanceof Created) {
                $talent->status->transitionTo(Draft::class);
            }
            $talent->status->transitionTo(Unpublished::class);
        } elseif (! $ready && $wasReady === true && ($talent->status instanceof Live || $talent->status instanceof Unpublished)) {
            $talent->status->transitionTo(Draft::class);
        }

        return $talent;
    }
}
