<?php

namespace App\States\TalentProfile;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Talent profile lifecycle (talent-spec):
 * created → draft ⇄ live ⇄ unpublished → suspended/archived → (soft-)deleted.
 * Draft ⇄ Live is automatic since ADR-AC (SyncGoLiveState: saving the Go Live
 * steps goes Live; breaking one goes back to Draft). Unpublished = held private.
 *
 * `Draft/Unpublished → Live` runs through the guarded {@see ToLive} transition
 * (a profile can't go live without a display name). Publishing side effects
 * (is_published + published_at) are applied by App\Listeners\SyncStateProjections.
 */
abstract class TalentProfileState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Created::class)
            ->allowTransition(Created::class, Draft::class)
            ->allowTransition(Draft::class, Live::class, ToLive::class)
            ->allowTransition(Unpublished::class, Live::class, ToLive::class)
            ->allowTransition(Live::class, Unpublished::class)
            // ADR-AC: a save that breaks a Go Live step takes the profile back to Draft (SyncGoLiveState).
            ->allowTransition(Live::class, Draft::class)
            ->allowTransition([Draft::class, Live::class, Unpublished::class], Suspended::class)
            ->allowTransition(Suspended::class, Unpublished::class)
            ->allowTransition([Draft::class, Live::class, Unpublished::class, Suspended::class], Archived::class);
    }
}
