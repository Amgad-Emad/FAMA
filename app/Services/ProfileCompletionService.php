<?php

namespace App\Services;

use App\Actions\Talent\SyncGoLiveState;
use App\Mail\ProfileStepsCompleted;
use App\Models\Talent;
use Illuminate\Support\Facades\Mail;

/**
 * THE home of the profile path (ADR-AC, 2026-11-04 — per the co-founder
 * prototype, profile.js FAMA_INDEX_REQ / FAMA_INDEX_REC / FAMA_PROFILE_DONE).
 * One path in two parts:
 *
 *   Go Live   — the three steps that make the profile Live (its link works and
 *               can be shared): display name · a skill AND a speciality · city.
 *               Nobody approves anything: a profile whose Go Live steps are
 *               saved goes Live by itself ({@see SyncGoLiveState}).
 *   Stand out — advisory, never a gate. Its first step ("something to show")
 *               lists a Live profile in the Creatives Index; the rest is what
 *               gets a profile picked.
 *
 * "Profile done" = every Go Live AND Stand out step. It decides where a
 * talent lands after signing in (Feed when done, else Profile structure).
 * Reads are side-effect free; the only writes are the state sync and the
 * one-time "you're Live" e-mail stamp.
 */
class ProfileCompletionService extends Service
{
    /** The Go Live steps, in the order the tracker shows them. */
    public const GO_LIVE = ['name', 'skill', 'place'];

    /** The Stand out steps, most useful first ('show' is the Creatives Index step). */
    public const STAND_OUT = ['show', 'projects3', 'headline', 'photo', 'brands', 'links', 'bio'];

    /** The Stand out step that lists a Live profile in the Creatives Index. */
    public const INDEX_STEP = 'show';

    /**
     * The Go Live steps with whether each is done (cheap: one relation).
     *
     *  name  — a display name
     *  skill — a skill with at least one speciality picked (specialities can
     *          only hang off skills the talent holds — SkillsService enforces it)
     *  place — a base city
     *
     * @return array<string, bool>
     */
    public function goLiveSteps(Talent $talent): array
    {
        $talent->loadMissing('specialities');

        return [
            'name' => trim((string) $talent->display_name) !== '',
            'skill' => $talent->specialities->isNotEmpty(),
            'place' => trim((string) $talent->base_city) !== '',
        ];
    }

    /**
     * The Stand out steps with whether each is done.
     *
     *  show      — something to show: a project (owned or an accepted credit),
     *              three gallery photos, or a showreel
     *  projects3 — three projects (owned or accepted credits, counted once)
     *  headline  — an English headline
     *  photo     — an uploaded avatar (the initials fallback does not count)
     *  brands    — experience: at least one brand collab
     *  links     — at least one social link (Instagram or a website, any platform)
     *  bio       — an English bio
     *
     * @return array<string, bool>
     */
    public function standOutSteps(Talent $talent): array
    {
        $talent->loadMissing('media');
        $projects = $this->projectCount($talent);

        return [
            'show' => $projects > 0
                || $talent->portfolioItems()->where('media_type', 'image')->count() >= 3
                || $talent->showreels()->exists(),
            'projects3' => $projects >= 3,
            'headline' => trim((string) $talent->getTranslation('headline', 'en', false)) !== '',
            'photo' => $talent->getFirstMedia('avatar') !== null,
            'brands' => $talent->brandCollabs()->exists(),
            'links' => $talent->socialHandles()->exists(),
            'bio' => trim((string) $talent->getTranslation('bio', 'en', false)) !== '',
        ];
    }

    /**
     * Every step (Go Live first, then Stand out) with whether it is done.
     *
     * @return array<string, bool>
     */
    public function steps(Talent $talent): array
    {
        return $this->goLiveSteps($talent) + $this->standOutSteps($talent);
    }

    /** Every Go Live step is saved — the profile may be (and auto-becomes) Live. */
    public function canGoLive(Talent $talent): bool
    {
        return ! in_array(false, $this->goLiveSteps($talent), true);
    }

    /**
     * @deprecated Kept only so the shared nav keeps rendering until it moves to
     *             canGoLive(); means "the Go Live steps are done" (ADR-AC).
     */
    public function isComplete(Talent $talent): bool
    {
        return $this->canGoLive($talent);
    }

    /**
     * Listed in the Creatives Index: Live AND something to show (so the index
     * never lists a profile with no work).
     */
    public function isListed(Talent $talent): bool
    {
        return (bool) $talent->is_published && $this->standOutSteps($talent)[self::INDEX_STEP];
    }

    /** Every Go Live and Stand out step done. */
    public function isProfileDone(Talent $talent): bool
    {
        return ! in_array(false, $this->steps($talent), true);
    }

    /**
     * Where a signed-in talent lands (login, and the site root): Projects once
     * the profile is done (13 Nov 2026 review: the portfolio first, then the
     * feed), otherwise Profile structure, where the next step is.
     */
    public function homeRoute(Talent $talent): string
    {
        return $this->isProfileDone($talent) ? 'talent.projects' : 'talent.profile.edit';
    }

    /**
     * The editor's view of the path, from SAVED data only (never the draft):
     * every step, Live/listed/done flags and the lifecycle status.
     *
     * @return array<string, mixed>
     */
    public function state(Talent $talent): array
    {
        $steps = $this->steps($talent);
        $published = (bool) $talent->is_published;

        return [
            'steps' => $steps,
            'can_go_live' => ! in_array(false, array_intersect_key($steps, array_flip(self::GO_LIVE)), true),
            'is_published' => $published,
            'listed' => $published && $steps[self::INDEX_STEP],
            'profile_done' => ! in_array(false, $steps, true),
            'status' => $talent->status->getValue(),
        ];
    }

    /**
     * Call after any write that can change a Go Live step. Moves the profile
     * Live (or back out, see SyncGoLiveState) and sends the one-time "you're
     * Live" e-mail. `$wasReady` = canGoLive() captured BEFORE the write.
     */
    public function syncAfterWrite(Talent $talent, ?bool $wasReady = null): void
    {
        $this->runInTransaction(
            fn () => app(SyncGoLiveState::class)($talent, $wasReady),
            ['talent_id' => $talent->id],
        );

        $this->notifyIfComplete($talent);
    }

    /**
     * The FIRST time the Go Live steps are done AND the profile is Live, stamp
     * `steps_completed_at` and e-mail "you're Live". The stamp is set with a
     * conditional UPDATE, so two racing requests can't both send. A profile held
     * Private (Unpublished/Suspended) gets nothing until it actually goes Live —
     * the e-mail must never claim a link works when it doesn't.
     */
    public function notifyIfComplete(Talent $talent): void
    {
        if ($talent->steps_completed_at !== null || ! $talent->is_published) {
            return;
        }

        $talent->unsetRelation('specialities');
        if (! $this->canGoLive($talent)) {
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

    /**
     * Projects on the talent's portfolio: owned ones plus accepted credits on
     * projects they don't own (a project owned AND credited counts once).
     */
    private function projectCount(Talent $talent): int
    {
        $owned = $talent->projects()->pluck('id');

        return $owned->count() + $talent->creditedProjects()->whereNotIn('projects.id', $owned)->count();
    }
}
