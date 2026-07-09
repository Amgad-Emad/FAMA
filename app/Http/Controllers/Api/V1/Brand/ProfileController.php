<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Models\BrandImage;
use App\Models\BrandSocialHandle;
use App\Services\BrandOnboardingService;
use App\Support\Brand\BrandOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Brand · Profile
 *
 * @authenticated
 *
 * The authenticated brand's own profile — core fields, aesthetic (references +
 * mood tags), the image gallery and social handles. Media flows through the media
 * library; the aesthetic write reuses the transactional onboarding service.
 * Translatable `description` is returned as a per-locale map so the app can edit
 * both languages.
 */
class ProfileController extends BrandApiController
{
    public function __construct(private readonly BrandOnboardingService $onboarding) {}

    /**
     * Get my profile
     */
    public function show(): JsonResponse
    {
        $brand = $this->brand()->load(['aesthetic.moodTags', 'socialHandles', 'images.media', 'media']);

        return response()->success([
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
            'description' => $brand->getTranslations('description'),
            'industry' => $brand->industry,
            'brand_stage' => $brand->brand_stage,
            'base_city' => $brand->base_city,
            'base_country' => $brand->base_country,
            'geographic_reach' => $brand->geographic_reach,
            'website' => $brand->website,
            'logo_url' => $brand->logo_url,
            'cover_image_url' => $brand->cover_image_url,
            'is_complete' => (bool) $brand->is_complete,
            'is_published' => (bool) $brand->is_published,
            'aesthetic' => [
                'brand_references' => $brand->aesthetic?->brand_references,
                'mood_tags' => $brand->aesthetic?->moodTags->pluck('tag')->values() ?? [],
            ],
            'social_handles' => $brand->socialHandles->map(fn (BrandSocialHandle $h) => [
                'id' => $h->id, 'platform' => $h->platform, 'handle' => $h->handle, 'url' => $h->url,
            ])->values(),
            'images' => $brand->images->map(fn (BrandImage $i) => [
                'id' => $i->id, 'image_url' => $i->image_url, 'thumbnail_url' => $i->thumbnail_url, 'position' => (int) $i->position,
            ])->values(),
        ]);
    }

    /**
     * Update my core profile
     */
    public function update(Request $request): JsonResponse
    {
        $this->brand()->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'industry' => ['nullable', Rule::in(BrandOptions::INDUSTRIES)],
            'brand_stage' => ['nullable', Rule::in(BrandOptions::STAGES)],
            'base_city' => ['nullable', 'string', 'max:255'],
            'base_country' => ['nullable', 'string', 'max:255'],
            'geographic_reach' => ['nullable', Rule::in(BrandOptions::REACH)],
            'website' => ['nullable', 'url', 'max:255'],
        ]));

        return response()->success(null, __('Profile updated.'));
    }

    /**
     * Upload my logo
     *
     * Multipart `file`. Returns the resolved logo URL.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'image', 'max:5120']]);
        $brand = $this->brand();
        $brand->addMediaFromRequest('file')->toMediaCollection('logo');

        return response()->success(['logo_url' => $brand->fresh()->logo_url], __('Logo updated.'));
    }

    /**
     * Upload my cover image
     *
     * Multipart `file`. Returns the resolved cover URL.
     */
    public function uploadCover(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'image', 'max:5120']]);
        $brand = $this->brand();
        $brand->addMediaFromRequest('file')->toMediaCollection('cover');

        return response()->success(['cover_image_url' => $brand->fresh()->cover_image_url], __('Cover updated.'));
    }

    /**
     * Update my aesthetic
     *
     * References + mood tags. Reuses the transactional onboarding service.
     */
    public function updateAesthetic(Request $request): JsonResponse
    {
        $this->onboarding->aesthetic($this->brand(), $request->validate([
            'brand_references' => ['nullable', 'string', 'max:2000'],
            'mood_tags' => ['array'],
            'mood_tags.*' => [Rule::in(BrandOptions::MOODS)],
        ]));

        return response()->success(null, __('Aesthetic updated.'));
    }

    /**
     * List my images
     */
    public function images(): JsonResponse
    {
        $images = $this->brand()->images()->with('media')->orderBy('position')->get()->map(fn (BrandImage $image) => [
            'id' => $image->id,
            'image_url' => $image->image_url,
            'thumbnail_url' => $image->thumbnail_url,
            'position' => (int) $image->position,
        ]);

        return response()->success(['images' => $images]);
    }

    /**
     * Add an image
     *
     * Multipart `file`. Returns the created row + its URL.
     */
    public function addImage(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'image', 'max:5120']]);
        $brand = $this->brand();
        $image = $brand->images()->create(['position' => $brand->images()->count()]);
        $image->addMediaFromRequest('file')->toMediaCollection('image');

        return response()->success(
            ['id' => $image->id, 'image_url' => $image->fresh()->image_url],
            __('Image added.'),
            status: 201,
        );
    }

    /**
     * Remove an image
     */
    public function removeImage(BrandImage $image): JsonResponse
    {
        $this->ensureOwns($image);
        $image->delete();

        return response()->success(null, __('Image removed.'));
    }

    /**
     * List my social handles
     */
    public function social(): JsonResponse
    {
        $handles = $this->brand()->socialHandles()->orderBy('position')->get()->map(fn (BrandSocialHandle $handle) => [
            'id' => $handle->id,
            'platform' => $handle->platform,
            'handle' => $handle->handle,
            'url' => $handle->url,
        ]);

        return response()->success(['handles' => $handles]);
    }

    /**
     * Add a social handle
     */
    public function addSocial(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(BrandOptions::PLATFORMS)],
            'handle' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:255'],
        ]);
        $handle = $this->brand()->socialHandles()->create($data + ['position' => $this->brand()->socialHandles()->count()]);

        return response()->success(['id' => $handle->id], __('Handle added.'), status: 201);
    }

    /**
     * Remove a social handle
     */
    public function removeSocial(BrandSocialHandle $handle): JsonResponse
    {
        $this->ensureOwns($handle);
        $handle->delete();

        return response()->success(null, __('Handle removed.'));
    }
}
