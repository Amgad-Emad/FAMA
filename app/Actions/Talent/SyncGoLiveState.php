<?php

namespace App\Actions\Talent;

use App\Models\Talent;
use App\Services\ProfileCompletionService;
use App\States\TalentProfile\Created;
use App\States\TalentProfile\Draft;
use App\States\TalentProfile\Live;

/**
 * Keeps a talent's Live state in step with the Go Live steps (ADR-AC): no
 * publish button, no approval — saving the three Go Live steps IS going Live.
 *
 *  - ready + Created/Draft            → Live
 *  - not ready + Live + WAS ready      → Draft (this write broke a step; the
 *                                        editor warns before such a save)
 *  - Unpublished / Suspended / Archived → untouched: a deliberate hold (admin
 *                                        Unpublish/Suspend, or a reinstated
 *                                        profile) is never undone by a save.
 *
 * Going OUT is edge-triggered (`$wasReady === true`): a profile that was Live
 * before this rule existed (e.g. no speciality) is grandfathered, never
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
            $talent->status->transitionTo(Live::class);
        } elseif (! $ready && $wasReady === true && $talent->status instanceof Live) {
            $talent->status->transitionTo(Draft::class);
        }

        return $talent;
    }
}
