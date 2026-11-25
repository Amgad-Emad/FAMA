<?php

namespace App\States\TalentProfile;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Talent profile lifecycle — what the talent sees is DRAFT → PRIVATE → LIVE
 * (ADR-AQ, 2026-11-23; supersedes ADR-AC's auto-Live):
 *   Draft       (created/draft) — the Go Live steps aren't all saved yet.
 *   Private     (unpublished)   — the steps are saved; only the talent sees it.
 *                                 Reached BY ITSELF when the steps are saved
 *                                 (SyncGoLiveState), or when a Live profile is
 *                                 made private (talent or admin).
 *   Live        (live)          — the talent pressed Go Live; the link works.
 * Breaking a Go Live step on a Private or Live profile goes back to Draft.
 * Suspended/archived sit outside the three (Fama's hold / the end of the road).
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
            // ADR-AQ: saving the Go Live steps makes a Draft Private; breaking one makes it Draft again.
            ->allowTransition(Draft::class, Unpublished::class)
            ->allowTransition(Unpublished::class, Draft::class)
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
