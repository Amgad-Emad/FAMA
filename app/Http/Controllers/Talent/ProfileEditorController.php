<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\AddBlockRequest;
use App\Http\Requests\Talent\FillBlockRequest;
use App\Http\Requests\Talent\MoveBlockRequest;
use App\Http\Requests\Talent\ReorderRequest;
use App\Http\Requests\Talent\UpdateAvatarRequest;
use App\Http\Requests\Talent\UpdateCoreProfileRequest;
use App\Http\Requests\Talent\UpdatePricingRateRequest;
use App\Http\Resources\BlockTypeResource;
use App\Http\Resources\ProfileBlockResource;
use App\Http\Resources\TalentTypeResource;
use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\TalentType;
use App\Services\ProfileBlockService;
use App\Services\TalentProfileService;
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
    ) {}

    public function edit(): View
    {
        $talent = $this->talent()->load(['profileBlocks.blockType', 'talentTypes']);
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
        ]);
    }

    /**
     * The talent's skills ordered primary-first, then by pivot position (ADR-Q).
     *
     * @return Collection<int, TalentType>
     */
    private function orderedSkills(\App\Models\Talent $talent): Collection
    {
        return $talent->talentTypes
            ->sortBy(fn (TalentType $t) => [$t->pivot->is_primary ? 0 : 1, (int) $t->pivot->position])
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
            'slug' => $talent->slug,
            'base_city' => $talent->base_city,
            'base_country' => $talent->base_country,
            'booking_type' => $talent->booking_type,
            'booking_value' => $talent->booking_value,
        ], __('Profile updated.'));
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
