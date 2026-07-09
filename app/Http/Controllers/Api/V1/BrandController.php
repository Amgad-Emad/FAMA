<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BrandResource;
use App\Http\Resources\CampaignResource;
use App\Models\Brand;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;

/**
 * @group Discovery
 *
 * Public, read-only brand profiles + public campaigns for the mobile app.
 * Published brands / public campaigns only, resolved by slug.
 */
class BrandController extends Controller
{
    /**
     * Show a brand
     *
     * The public brand profile, resolved by slug: credibility, aesthetic, social
     * handles, image gallery, approved reviews and public campaigns. `description`
     * comes back in the request locale (Accept-Language). 404 if unpublished.
     *
     * @unauthenticated
     */
    public function show(Brand $brand): JsonResponse
    {
        abort_unless((bool) $brand->is_published, 404);

        $brand->load([
            'media',
            'credibility',
            'aesthetic.moodTags',
            'images.media',
            'socialHandles',
            'brandReviews' => fn ($query) => $query->where('is_approved', true)->with('talent')->latest(),
            'campaigns' => fn ($query) => $query->where('is_public', true)->where('status', '!=', 'cancelled')->with('media')->latest(),
        ]);

        return response()->success(new BrandResource($brand));
    }

    /**
     * Show a public campaign
     *
     * One public campaign under a published brand, resolved by slug and scoped to
     * the brand in the path (404 otherwise).
     *
     * @unauthenticated
     */
    public function campaign(Brand $brand, Campaign $campaign): JsonResponse
    {
        abort_unless((bool) $brand->is_published && (bool) $campaign->is_public, 404);
        abort_unless((int) $campaign->brand_id === (int) $brand->getKey(), 404);

        $campaign->load(['media', 'talentTypes', 'gallery.media']);

        return response()->success(new CampaignResource($campaign));
    }
}
