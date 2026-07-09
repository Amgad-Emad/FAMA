<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Http\Requests\Talent\AddBlockRequest;
use App\Http\Requests\Talent\FillBlockRequest;
use App\Http\Requests\Talent\ReorderRequest;
use App\Http\Requests\Talent\UpdateCoreProfileRequest;
use App\Http\Resources\BlockTypeResource;
use App\Http\Resources\ProfileBlockResource;
use App\Http\Resources\TalentTypeResource;
use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Services\ProfileBlockService;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Talent · Profile
 *
 * @authenticated
 *
 * The authenticated talent's own profile — core `talents` fields, the hero image,
 * and the reorderable `profile_blocks` layer with the eligibility-driven picker.
 * Thin wrapper over ProfileBlockService / TalentProfileService (the same services
 * the web editor uses). Translatable fields are returned as per-locale maps so the
 * app can edit both languages.
 */
class ProfileController extends TalentApiController
{
    public function __construct(
        private readonly ProfileBlockService $blocks,
        private readonly TalentProfileService $profile,
    ) {}

    /**
     * Get my profile
     *
     * Core fields (translatables as per-locale maps), linked professions and the
     * ordered profile blocks.
     */
    public function show(): JsonResponse
    {
        $talent = $this->talent()->load(['profileBlocks.blockType', 'talentTypes', 'media']);

        return response()->success([
            'id' => $talent->id,
            'slug' => $talent->slug,
            'display_name' => $talent->display_name,
            'headline' => $talent->getTranslations('headline'),
            'bio' => $talent->getTranslations('bio'),
            'avatar_url' => $talent->avatar_url,
            'hero_image_url' => $talent->hero_image_url,
            'base_city' => $talent->base_city,
            'base_country' => $talent->base_country,
            'availability_status' => $talent->availability_status->getValue(),
            'rate_tier' => $talent->rate_tier,
            'booking_type' => $talent->booking_type,
            'booking_value' => $talent->booking_value,
            'willing_to_travel' => (bool) $talent->willing_to_travel,
            'travel_regions' => $talent->travel_regions,
            'is_published' => (bool) $talent->is_published,
            'status' => $talent->status->getValue(),
            'talent_types' => TalentTypeResource::collection($talent->talentTypes),
            'blocks' => ProfileBlockResource::collection($talent->profileBlocks),
        ]);
    }

    /**
     * Update my core profile
     */
    public function update(UpdateCoreProfileRequest $request): JsonResponse
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

    /**
     * Upload my hero image
     *
     * Multipart `image`. Returns the resolved hero URL (medialibrary).
     */
    public function uploadHero(Request $request): JsonResponse
    {
        $request->validate(['image' => ['required', 'image', 'max:8192']]);
        $talent = $this->profile->setHeroImage($this->talent(), $request->file('image'));

        return response()->success(['hero_image_url' => $talent->hero_image_url], __('Hero image updated.'));
    }

    /**
     * List my profile blocks
     */
    public function blocks(): JsonResponse
    {
        $talent = $this->talent()->load('profileBlocks.blockType');

        return response()->success(ProfileBlockResource::collection($talent->profileBlocks));
    }

    /**
     * List addable block types
     *
     * The block types still available to add, filtered by the talent's profession
     * eligibility.
     */
    public function picker(): JsonResponse
    {
        return response()->success(BlockTypeResource::collection($this->blocks->availableBlockTypes($this->talent())));
    }

    /**
     * Add a profile block
     */
    public function addBlock(AddBlockRequest $request): JsonResponse
    {
        $blockType = BlockType::findOrFail($request->integer('block_type_id'));
        $block = $this->blocks->addBlock($this->talent(), $blockType);

        return response()->success(new ProfileBlockResource($block->load('blockType')), __('Block added.'), status: 201);
    }

    /**
     * Fill / edit a profile block
     */
    public function fillBlock(FillBlockRequest $request, ProfileBlock $block): JsonResponse
    {
        $this->ensureOwns($block);
        $block = $this->blocks->fillBlock($block, $request->validated());

        return response()->success(new ProfileBlockResource($block->load('blockType')), __('Block saved.'));
    }

    /**
     * Reorder my profile blocks
     */
    public function reorderBlocks(ReorderRequest $request): JsonResponse
    {
        $this->blocks->reorder($this->talent(), $request->orderedIds());

        return response()->success(null, __('Order saved.'));
    }

    /**
     * Show / hide a profile block
     */
    public function toggleBlock(Request $request, ProfileBlock $block): JsonResponse
    {
        $this->ensureOwns($block);
        $block = $this->blocks->setVisibility($block, $request->boolean('is_visible'));

        return response()->success(new ProfileBlockResource($block->load('blockType')));
    }

    /**
     * Remove a profile block
     */
    public function removeBlock(ProfileBlock $block): JsonResponse
    {
        $this->ensureOwns($block);
        $this->blocks->removeBlock($block);

        return response()->success(null, __('Block removed.'));
    }
}
