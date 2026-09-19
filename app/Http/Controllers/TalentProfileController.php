<?php

namespace App\Http\Controllers;

use App\Events\TalentProfileViewed;
use App\Models\Talent;
use App\Models\TalentType;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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
     * Show a published talent's public profile by slug (404 otherwise). The active
     * tab comes from `?skill=` (when that skill has visible blocks) or the primary.
     */
    public function show(string $slug, \App\Feed\CurrentActor $currentActor, \App\Services\FollowService $follows): View
    {
        $talent = $this->publishedProfile($slug);

        TalentProfileViewed::dispatch($talent);

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
        ])->render();

        return response()->success(['html' => $html]);
    }

    /**
     * Load a published talent with everything the profile renders (eager, no N+1).
     */
    private function publishedProfile(string $slug): Talent
    {
        return Talent::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with($this->eagerLoads())
            ->firstOrFail();
    }

    /**
     * A skill's visible blocks in position order (the `hero` block is the header).
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\ProfileBlock>
     */
    private function blocksForSkill(Talent $talent, TalentType $skill): \Illuminate\Support\Collection
    {
        return $talent->profileBlocks
            ->where('talent_type_id', $skill->id)
            ->filter(fn ($block) => $block->blockType->key !== 'hero')
            ->sortBy('position')
            ->values();
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
