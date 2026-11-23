<?php

namespace App\Http\Controllers;

use App\Events\TalentProfileViewed;
use App\Feed\CurrentActor;
use App\Models\ProfileBlock;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\FollowService;
use App\Support\ProfileSections;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Public talent profile — `fama.com/{slug}` (schema-master §1, talent-spec).
 * Two stacked regions (ADR-R): identity + universal (always visible) then skill
 * tabs. The active (primary, or `?skill=`) tab renders server-side; other tabs are
 * fetched lazily via {@see tab()}. Everything is eager-loaded (no N+1); only
 * visible blocks + approved reviews load, so the templates stay presentational.
 */
class TalentProfileController extends Controller
{
    /**
     * Show a published talent's public profile by slug. The active tab comes from
     * `?skill=` (when that skill has visible blocks) or the first skill.
     *
     * A profile that is not Live yet answers visitors with the prototype's
     * not-live page ("This profile is still being set up.") — still a 404 status,
     * and nothing about the talent in it, so a private profile stays private.
     * An unknown slug is a plain 404.
     */
    public function show(string $slug, CurrentActor $currentActor, FollowService $follows): View|Response
    {
        $talent = $this->loadProfile($slug);

        if (! $this->isVisibleTo($talent)) {
            return response()->view('talent.not-live', [], 404);
        }

        // The owner previewing their own profile is not a view (and an
        // unpublished profile is only ever seen by its owner).
        if (! $this->isOwner($talent) && ! $this->isStaff()) {
            TalentProfileViewed::dispatch($talent);
        }

        // The viewer's follow state (one bulk query) so the CTA reflects it.
        // The button is hidden on your own profile (self-follow is forbidden).
        $actor = $currentActor->resolve();
        $isSelf = $actor instanceof Talent && (int) $actor->getKey() === (int) $talent->id;
        $following = $actor !== null
            ? ($follows->isFollowing($actor, collect([$talent]))['talent:'.$talent->id] ?? false)
            : false;

        return view('talent.profile', [
            'talent' => $talent,
            'canFollow' => ! $isSelf,
            'following' => $following,
            // Moderation's View panel embeds this page (?staff_preview=1): a strip says so.
            'staffPreview' => $this->isStaff() && request()->boolean('staff_preview'),
        ]);
    }

    /**
     * Lazily fetch one skill's rendered blocks (talent-spec lazy tabs). Returns the
     * envelope with the rendered HTML fragment; does NOT bump `view_count`.
     */
    public function tab(string $slug, string $skillSlug): JsonResponse
    {
        $talent = $this->publishedProfile($slug);

        $skill = $talent->talentTypes->firstWhere('slug', $skillSlug);
        abort_if($skill === null, 404);

        $blocks = $this->blocksForSkill($talent, $skill);
        abort_if($blocks->isEmpty(), 404);

        $html = view('talent.partials.skill-blocks', [
            'talent' => $talent,
            'skill' => $skill,
            'blocks' => $blocks,
            'isOwner' => $this->isOwner($talent),
        ])->render();

        return response()->success(['html' => $html]);
    }

    /**
     * Load a published talent with everything the profile renders (eager, no N+1);
     * 404 when the slug is unknown or the profile is not visible to this viewer.
     */
    private function publishedProfile(string $slug): Talent
    {
        $talent = $this->loadProfile($slug);

        abort_unless($this->isVisibleTo($talent), 404);

        return $talent;
    }

    /**
     * Load a talent by slug with everything the profile renders (404 if unknown).
     */
    private function loadProfile(string $slug): Talent
    {
        return Talent::query()
            ->where('slug', $slug)
            ->with($this->eagerLoads())
            ->firstOrFail();
    }

    /**
     * Whether the current viewer may see this profile. Unpublished profiles are
     * hidden from everyone except their owner, who gets the Preview (fama-ui
     * preview.html: "Your profile is private. This is how people will see it
     * once it's public."), and staff with moderate-content — moderation
     * previews a profile BEFORE verifying it, i.e. exactly while it is private.
     */
    private function isVisibleTo(Talent $talent): bool
    {
        return $talent->is_published || $this->isOwner($talent) || $this->isStaff();
    }

    /**
     * A signed-in admin who may moderate content (the moderation View panel).
     * Their visits are not profile views.
     */
    private function isStaff(): bool
    {
        return (bool) auth('admin')->user()?->can('moderate-content');
    }

    /**
     * Whether the signed-in talent is the profile's owner (the Preview case).
     */
    private function isOwner(Talent $talent): bool
    {
        $viewer = auth('talent')->user();

        return $viewer !== null && (int) $viewer->getKey() === (int) $talent->getKey();
    }

    /**
     * A skill's visible blocks in position order (the `hero` block is the header).
     *
     * @return Collection<int, ProfileBlock>
     */
    private function blocksForSkill(Talent $talent, TalentType $skill): Collection
    {
        // One rule set with the profile page — incl. "Projects always show while
        // the tab has work" (App\Support\ProfileSections).
        return ProfileSections::forScope($talent, (int) $skill->id);
    }

    /**
     * The eager-load map shared by both endpoints (visible blocks + all content).
     *
     * @return array<int|string, mixed>
     */
    private function eagerLoads(): array
    {
        return [
            'media',
            'talentTypes',
            // Claimed specialities (ADR-Y) — the skill tabs render them, so they
            // must ride along with the profile rather than resolve per tab.
            'specialities',
            // ADR-Z sections: About (social), Recognitions, Willing to explore.
            'socialHandles',
            'recognitions' => fn (Builder $query) => $query->where('is_approved', true),
            'recognitions.author',
            'interests.talentType',
            'interests.speciality',
            'profileBlocks' => fn (Builder $query) => $query->where('is_visible', true),
            'portfolioItems.media',
            'compCard',
            'reviews' => fn (Builder $query) => $query->approved(),
            'brandCollabs.media',
            'lookTypes',
            'digitals.media',
            'showreels.media',
            'equipment',
            'projects.media',
            // Projects carry one or more skills (ADR-X) — the tab test reads them.
            'projects.talentTypes',
            'projects.specialities',
            // Work this talent was CREDITED on and accepted (ADR-AA) — it renders
            // in the credited skill's tab alongside their own projects.
            'creditedProjects.media',
            'creditedProjects.talent',
            'softwareStack.media',
        ];
    }
}
