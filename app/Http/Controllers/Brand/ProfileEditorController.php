<?php

namespace App\Http\Controllers\Brand;

use App\Models\BrandImage;
use App\Models\BrandSocialHandle;
use App\Services\BrandOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Brand profile editor (brand-spec) — core fields, aesthetic (references + mood
 * tags), the image gallery, and social handles. Media flows through the media
 * library; the aesthetic write reuses the transactional onboarding service.
 */
class ProfileEditorController extends BrandController
{
    private const INDUSTRIES = ['fashion', 'beauty', 'food_beverage', 'lifestyle', 'tech', 'other'];

    private const STAGES = ['new', 'growing', 'established'];

    private const REACH = ['same_city', 'mena', 'international'];

    private const MOODS = ['editorial', 'minimal', 'bold', 'warm', 'dark', 'playful', 'luxurious', 'raw', 'nostalgic', 'commercial'];

    private const PLATFORMS = ['instagram', 'tiktok', 'x', 'linkedin', 'youtube', 'facebook', 'behance', 'website', 'other'];

    public function __construct(private readonly BrandOnboardingService $onboarding) {}

    public function edit(): View
    {
        $brand = $this->brand()->loadMissing('aesthetic.moodTags', 'socialHandles');

        return view('brand.profile', [
            'brand' => $brand,
            'moods' => self::MOODS,
            'platforms' => self::PLATFORMS,
        ]);
    }

    public function updateCore(Request $request): JsonResponse
    {
        $this->brand()->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'industry' => ['nullable', Rule::in(self::INDUSTRIES)],
            'brand_stage' => ['nullable', Rule::in(self::STAGES)],
            'base_city' => ['nullable', 'string', 'max:255'],
            'base_country' => ['nullable', 'string', 'max:255'],
            'geographic_reach' => ['nullable', Rule::in(self::REACH)],
            'website' => ['nullable', 'url', 'max:255'],
        ]));

        return response()->success(null, __('Profile updated.'));
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'image', 'max:5120']]);
        $brand = $this->brand();
        $brand->addMediaFromRequest('file')->toMediaCollection('logo');

        return response()->success(['logo_url' => $brand->fresh()->logo_url], __('Logo updated.'));
    }

    public function uploadCover(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'image', 'max:5120']]);
        $brand = $this->brand();
        $brand->addMediaFromRequest('file')->toMediaCollection('cover');

        return response()->success(['cover_image_url' => $brand->fresh()->cover_image_url], __('Cover updated.'));
    }

    public function updateAesthetic(Request $request): JsonResponse
    {
        $this->onboarding->aesthetic($this->brand(), $request->validate([
            'brand_references' => ['nullable', 'string', 'max:2000'],
            'mood_tags' => ['array'],
            'mood_tags.*' => [Rule::in(self::MOODS)],
        ]));

        return response()->success(null, __('Aesthetic updated.'));
    }

    public function images(): JsonResponse
    {
        $images = $this->brand()->images()->with('media')->get()->map(fn (BrandImage $image) => [
            'id' => $image->id,
            'image_url' => $image->image_url,
            'thumbnail_url' => $image->thumbnail_url,
            'position' => (int) $image->position,
        ]);

        return response()->success(['images' => $images]);
    }

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

    public function removeImage(BrandImage $image): JsonResponse
    {
        $this->ensureOwns($image);
        $image->delete();

        return response()->success(null, __('Image removed.'));
    }

    public function socialData(): JsonResponse
    {
        $handles = $this->brand()->socialHandles()->get()->map(fn (BrandSocialHandle $handle) => [
            'id' => $handle->id,
            'platform' => $handle->platform,
            'handle' => $handle->handle,
            'url' => $handle->url,
        ]);

        return response()->success(['handles' => $handles]);
    }

    public function addSocial(Request $request): JsonResponse
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(self::PLATFORMS)],
            'handle' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:255'],
        ]);
        $handle = $this->brand()->socialHandles()->create($data + ['position' => $this->brand()->socialHandles()->count()]);

        return response()->success(['id' => $handle->id], __('Handle added.'), status: 201);
    }

    public function removeSocial(BrandSocialHandle $handle): JsonResponse
    {
        $this->ensureOwns($handle);
        $handle->delete();

        return response()->success(null, __('Handle removed.'));
    }
}
