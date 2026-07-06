<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\AddBlockRequest;
use App\Http\Requests\Talent\FillBlockRequest;
use App\Http\Requests\Talent\ReorderRequest;
use App\Http\Requests\Talent\UpdateCoreProfileRequest;
use App\Http\Resources\BlockTypeResource;
use App\Http\Resources\ProfileBlockResource;
use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Services\ProfileBlockService;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profile editor (talent-spec) — core `talents` fields + the reorderable
 * `profile_blocks` layer with the eligibility-driven block picker. All logic
 * delegates to ProfileBlockService / TalentProfileService; every non-page action
 * returns the JSON envelope for the Alpine/http.js front-end.
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

        return view('talent.profile-editor', [
            'talent' => $talent,
            'blocks' => ProfileBlockResource::collection($talent->profileBlocks),
            'picker' => BlockTypeResource::collection($this->blocks->availableBlockTypes($talent)),
        ]);
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
            'willing_to_travel' => (bool) $talent->willing_to_travel,
            'travel_regions' => $talent->travel_regions,
            'rate_tier' => $talent->rate_tier,
        ], __('Profile updated.'));
    }

    public function uploadHero(Request $request): JsonResponse
    {
        $request->validate(['image' => ['required', 'image', 'max:8192']]);
        $talent = $this->profile->setHeroImage($this->talent(), $request->file('image'));

        return response()->success(['hero_image_url' => $talent->hero_image_url], __('Hero image updated.'));
    }

    public function blocks(): JsonResponse
    {
        $talent = $this->talent()->load('profileBlocks.blockType');

        return response()->success(ProfileBlockResource::collection($talent->profileBlocks));
    }

    public function picker(): JsonResponse
    {
        return response()->success(BlockTypeResource::collection($this->blocks->availableBlockTypes($this->talent())));
    }

    public function addBlock(AddBlockRequest $request): JsonResponse
    {
        $blockType = BlockType::findOrFail($request->integer('block_type_id'));
        $block = $this->blocks->addBlock($this->talent(), $blockType);

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
        $this->blocks->reorder($this->talent(), $request->orderedIds());

        return response()->success(null, __('Order saved.'));
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
