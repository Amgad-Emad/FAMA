<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\AddBlockRequest;
use App\Http\Requests\Talent\FillBlockRequest;
use App\Http\Requests\Talent\MoveBlockRequest;
use App\Http\Requests\Talent\ReorderRequest;
use App\Http\Requests\Talent\UpdateAvatarRequest;
use App\Http\Requests\Talent\UpdateCoreProfileRequest;
use App\Http\Requests\Talent\UpdateNowRequest;
use App\Http\Requests\Talent\UpdatePricingRateRequest;
use App\Http\Requests\Talent\UpdateSectionOrderRequest;
use App\Http\Requests\Talent\UpdateSocialHandlesRequest;
use App\Http\Resources\BlockTypeResource;
use App\Http\Resources\ProfileBlockResource;
use App\Http\Resources\SpecialityResource;
use App\Http\Resources\TalentInterestResource;
use App\Http\Resources\TalentTypeResource;
use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Speciality;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\ProfileBlockService;
use App\Services\ProfileCompletionService;
use App\Services\ProfileViewStatsService;
use App\Services\TalentProfileService;
use App\Support\ContentRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Profile editor (talent-spec) — the single profile surface. Core `talents` fields
 * (identity + username), the Skills section, the Pricing rate, the publish toggle,
 * and the reorderable `profile_blocks` layer with the eligibility-driven block
 * picker. All logic delegates to ProfileBlockService / TalentProfileService; every
 * non-page action returns the JSON envelope for the Alpine/http.js front-end.
 */
class ProfileEditorController extends TalentController
{
    public function __construct(
        private readonly ProfileBlockService $blocks,
        private readonly TalentProfileService $profile,
        private readonly ProfileCompletionService $completion,
        private readonly ProfileViewStatsService $viewStats,
    ) {}

    /**
     * The Profile structure page (fama-ui): identity, availability, links,
     * skills + specialities, sections (blocks), willing-to-explore, experience
     * and pricing, plus the folded-in dashboard (completion steps + views).
     */
    public function edit(): View
    {
        $talent = $this->talent()->load([
            'profileBlocks.blockType', 'talentTypes', 'specialities',
            'socialHandles', 'interests.talentType', 'interests.speciality',
            'brandCollabs.media', 'media',
        ]);
        $skills = $this->orderedSkills($talent);

        $catalog = BlockType::query()->where('is_active', true)
            ->with(['categories', 'talentTypes'])->orderBy('position')->get();

        return view('talent.profile-editor', [
            'talent' => $talent,
            'blocks' => ProfileBlockResource::collection($talent->profileBlocks),
            'catalog' => BlockTypeResource::collection($catalog),
            'skills' => TalentTypeResource::collection($skills),
            'availableSkills' => TalentTypeResource::collection(
                TalentType::whereNotIn('id', $talent->talentTypes->pluck('id'))->orderBy('id')->get()
            ),
            // The controlled speciality vocabulary + current picks, keyed by
            // skill id (ADR-Y) — the same shape the skills endpoint returns, so
            // the picker re-renders from either.
            'specialities' => $this->specialityPayload($talent, $skills),
            // ADR-Z sections. `now` carries the humanised stamp so the editor
            // can render "as of …" on first paint without a round trip.
            'now' => [
                'will_travel' => (bool) $talent->will_travel,
                'is_available' => (bool) $talent->is_available,
                'availability_changed_at' => $talent->availability_changed_at?->toIso8601String(),
                'availability_changed_for_humans' => $talent->availability_changed_at?->diffForHumans(),
            ],
            'social' => $this->socialPayload($talent),
            'interests' => TalentInterestResource::collection($talent->interests)->resolve(),
            // Every skill, not just the talent's own — this section names
            // directions they do NOT yet work in (ADR-Z).
            'interestSkills' => $this->interestSkillPayload(),
            // Block types whose content is editable somewhere — the rest
            // (comp_card) must not offer a dead "Edit content" link. `projects`
            // is editable on its own page (ADR-X), not the content page.
            'contentKeys' => [...ContentRegistry::keys(), 'reviews', 'projects', 'recognitions', 'interests'],
            // The fama-ui page folds the old dashboard in (2026-10-12): the steps
            // to a complete profile (shown while Private) and the views card.
            'completion' => $this->completion->steps($talent),
            'views' => [
                'total' => (int) $talent->view_count,
                'weekly' => $this->viewStats->weeklyComparison($talent),
            ],
            // Brand collabs — the "Experience" panel lists them read-only; they
            // are edited on the profile-content page.
            'collabs' => $talent->brandCollabs->sortBy('position')->map(fn ($collab) => [
                'id' => (int) $collab->id,
                'name' => (string) $collab->brand_name,
                'project' => (string) ($collab->project_title ?? ''),
                'year' => $collab->year,
                'logo' => $collab->brand_logo_url,
            ])->values()->all(),
        ]);
    }

    /**
     * The full pickable vocabulary for "willing to explore" — EVERY skill with
     * its specialities, because the section exists to name directions the talent
     * does not yet work in (ADR-Z). One query for the whole speciality catalog.
     *
     * @return list<array<string, mixed>>
     */
    private function interestSkillPayload(): array
    {
        $specialities = Speciality::query()
            ->selectable()
            ->orderBy('position')->orderBy('id')
            ->get()
            ->groupBy('talent_type_id');

        $locale = app()->getLocale();

        return TalentType::query()->orderBy('id')->get()->map(fn (TalentType $type) => [
            'id' => (int) $type->id,
            'slug' => $type->slug,
            'name' => $type->getTranslation('name', $locale, false) ?: $type->getTranslation('name', 'en', false),
            'icon' => $type->icon,
            'specialities' => $specialities->get($type->id, collect())->map(fn (Speciality $s) => [
                'id' => (int) $s->id,
                'name' => $s->getTranslation('name', $locale, false) ?: $s->getTranslation('name', 'en', false),
            ])->values()->all(),
        ])->values()->all();
    }

    /**
     * The talent's social handles, shaped for the editor's list.
     *
     * @return list<array<string, mixed>>
     */
    private function socialPayload(Talent $talent): array
    {
        return $talent->load('socialHandles')->socialHandles
            ->map(fn ($handle) => [
                'platform' => $handle->platform,
                'handle' => $handle->handle,
                'url' => $handle->url,
            ])->values()->all();
    }

    /**
     * The speciality options + picks for each skill the talent holds, keyed by
     * skill id. ONE query for the whole vocabulary — the picker needs every
     * skill's options up front because the sections open independently.
     *
     * @param  Collection<int, TalentType>  $skills
     * @return array<int, array<string, mixed>>
     */
    private function specialityPayload(Talent $talent, Collection $skills): array
    {
        $catalog = Speciality::query()
            ->selectable()
            ->whereIn('talent_type_id', $skills->pluck('id'))
            ->orderBy('position')->orderBy('id')
            ->get()
            ->groupBy('talent_type_id');

        $claimed = $talent->specialities->groupBy('talent_type_id');

        return $skills->mapWithKeys(fn (TalentType $type) => [
            $type->id => [
                'options' => SpecialityResource::collection($catalog->get($type->id, collect()))->resolve(),
                'selected' => $claimed->get($type->id, collect())->pluck('id')->map('intval')->values()->all(),
            ],
        ])->all();
    }

    /**
     * The talent's skills in their order (pivot position) — the tab order; the first opens first.
     *
     * @return Collection<int, TalentType>
     */
    private function orderedSkills(Talent $talent): Collection
    {
        return $talent->talentTypes
            ->sortBy(fn (TalentType $t) => [(int) $t->pivot->position, (int) $t->id])
            ->values();
    }

    /**
     * Resolve a scope id to one of the talent's skills (or null = universal).
     */
    private function scope(?int $typeId): ?TalentType
    {
        if ($typeId === null) {
            return null;
        }

        return $this->talent()->talentTypes()->whereKey($typeId)->first()
            ?? throw new InvalidArgumentException('That is not one of your skills.');
    }

    public function updateCore(UpdateCoreProfileRequest $request): JsonResponse
    {
        $talent = $this->profile->updateCore($this->talent(), $request->validated());

        return response()->success([
            'display_name' => $talent->display_name,
            'headline' => $talent->getTranslations('headline'),
            'bio' => $talent->getTranslations('bio'),
            'worldview' => $talent->getTranslations('worldview'),
            'slug' => $talent->slug,
            'base_city' => $talent->base_city,
            'base_country' => $talent->base_country,
        ], __('Profile updated.'));
    }

    /**
     * The NOW strip (ADR-Z) — the two time-sensitive toggles. Returns the
     * stamp so the editor can show "as of …" without a re-fetch.
     */
    public function updateNow(UpdateNowRequest $request): JsonResponse
    {
        $talent = $this->profile->updateNow($this->talent(), $request->validated());

        return response()->success([
            'will_travel' => (bool) $talent->will_travel,
            'is_available' => (bool) $talent->is_available,
            'availability_changed_at' => $talent->availability_changed_at?->toIso8601String(),
            'availability_changed_for_humans' => $talent->availability_changed_at?->diffForHumans(),
        ], __('Saved.'));
    }

    /**
     * Replace the social handles with one ordered list (ADR-Z, About).
     */
    public function updateSocial(UpdateSocialHandlesRequest $request): JsonResponse
    {
        $talent = $this->profile->updateSocialHandles($this->talent(), $request->validated('handles'));

        return response()->success(
            ['handles' => $this->socialPayload($talent)],
            __('Links saved.'),
        );
    }

    /**
     * Upload / replace the profile image (avatar). Returns the resolved URL.
     */
    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $talent = $this->profile->updateAvatar($this->talent(), $request->file('avatar'));

        return response()->success(['avatar_url' => $talent->avatar_url], __('Profile image updated.'));
    }

    /**
     * Remove the profile image (falls back to the initials avatar).
     */
    public function removeAvatar(): JsonResponse
    {
        $talent = $this->profile->removeAvatar($this->talent());

        return response()->success(['avatar_url' => $talent->avatar_url], __('Profile image removed.'));
    }

    /**
     * Update (or clear) the indicative pricing rate — all-or-nothing (ADR-N).
     */
    public function updatePricingRate(UpdatePricingRateRequest $request): JsonResponse
    {
        $talent = $this->profile->updatePricingRate($this->talent(), $request->validated());

        return response()->success([
            'rate_unit' => $talent->rate_unit,
            'rate_amount' => $talent->rate_amount,
            'rate_currency' => $talent->rate_currency,
        ], __('Pricing rate updated.'));
    }

    /**
     * Reposition the universal section among the skill sections (the editor's
     * pill bar): 0 keeps it above the skill tabs on the public profile, any
     * higher index moves it below them.
     */
    public function updateSectionOrder(UpdateSectionOrderRequest $request): JsonResponse
    {
        $talent = $this->profile->updateUniversalPosition($this->talent(), $request->position());

        return response()->success([
            'universal_position' => (int) ($talent->meta['universal_position'] ?? 0),
        ], __('Sections updated.'));
    }

    /**
     * Publish / unpublish the profile from the editor (moved from Account).
     */
    public function publish(Request $request): JsonResponse
    {
        $publish = $request->boolean('publish');

        $talent = $publish
            ? $this->profile->publish($this->talent())
            : $this->profile->unpublish($this->talent());

        return response()->success([
            'is_published' => (bool) $talent->is_published,
            'status' => $talent->status->getValue(),
        ], $publish ? __('Profile published.') : __('Profile unpublished.'));
    }

    public function blocks(): JsonResponse
    {
        $talent = $this->talent()->load('profileBlocks.blockType');

        return response()->success(ProfileBlockResource::collection($talent->profileBlocks));
    }

    public function picker(Request $request): JsonResponse
    {
        $scope = $this->scope($request->has('talent_type_id') ? $request->integer('talent_type_id') : null);

        return response()->success(BlockTypeResource::collection($this->blocks->availableBlockTypes($this->talent(), $scope)));
    }

    public function addBlock(AddBlockRequest $request): JsonResponse
    {
        $blockType = BlockType::findOrFail($request->integer('block_type_id'));
        $scope = $this->scope($request->filled('talent_type_id') ? $request->integer('talent_type_id') : null);
        $block = $this->blocks->addBlock($this->talent(), $blockType, $scope);

        return response()->success(new ProfileBlockResource($block->load('blockType')), __('Block added.'), status: 201);
    }

    public function fillBlock(FillBlockRequest $request, ProfileBlock $block): JsonResponse
    {
        $this->ensureOwns($block);
        $block = $this->blocks->fillBlock($block, $request->validated());

        return response()->success(new ProfileBlockResource($block->load('blockType')), __('Block saved.'));
    }

    public function reorderBlocks(ReorderRequest $request): JsonResponse
    {
        $scope = $this->scope($request->filled('talent_type_id') ? $request->integer('talent_type_id') : null);
        $this->blocks->reorder($this->talent(), $scope, $request->orderedIds());

        return response()->success(null, __('Order saved.'));
    }

    /**
     * Move a block to another scope (a tab, or the universal section) — ADR-Q.
     */
    public function moveBlock(MoveBlockRequest $request, ProfileBlock $block): JsonResponse
    {
        $this->ensureOwns($block);
        $target = $this->scope($request->filled('talent_type_id') ? $request->integer('talent_type_id') : null);
        $block = $this->blocks->moveBlock($block, $target);

        return response()->success(new ProfileBlockResource($block->load('blockType')), __('Block moved.'));
    }

    public function toggleBlock(Request $request, ProfileBlock $block): JsonResponse
    {
        $this->ensureOwns($block);
        $block = $this->blocks->setVisibility($block, $request->boolean('is_visible'));

        return response()->success(new ProfileBlockResource($block->load('blockType')));
    }

    public function removeBlock(ProfileBlock $block): JsonResponse
    {
        $this->ensureOwns($block);
        $this->blocks->removeBlock($block);

        return response()->success(null, __('Block removed.'));
    }
}
