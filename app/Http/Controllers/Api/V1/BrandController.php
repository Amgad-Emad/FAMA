<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BrandResource;
use App\Http\Resources\Api\V1\CampaignResource;
use App\Models\Brand;
use App\Models\Campaign;
use App\Queries\BrandSearch;
use Illuminate\Http\JsonResponse;

/**
 * @group Discovery
 *
 * Public, read-only brand directory + profiles + public campaigns for the mobile
 * app. Published brands / public campaigns only.
 */
class BrandController extends Controller
{
    public function __construct(private readonly BrandSearch $search) {}

    /**
     * List brands
     *
     * Paginated, filterable public brand directory (published brands only).
     * Whitelisted filters and sorts mirror the web search contract.
     *
     * @unauthenticated
     *
     * @queryParam filter[industry] string Exact industry. Example: food_beverage
     * @queryParam filter[stage] string Exact brand stage. Example: growing
     * @queryParam filter[reach] string Exact geographic reach. Example: mena
     * @queryParam filter[city] string Partial city match. Example: Cairo
     * @queryParam filter[verified] boolean Only verified brands. Example: 1
     * @queryParam filter[q] string Free-text name match. Example: nomad
     * @queryParam sort string created_at or name (prefix - for descending). Example: -created_at
     * @queryParam page integer The page number. Example: 1
     */
    public function index(): JsonResponse
    {
        $paginator = $this->search->paginate();

        return response()->paginated($paginator, BrandResource::collection($paginator->getCollection()));
    }

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
