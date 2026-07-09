<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;

/**
 * @group Discovery
 *
 * Public, read-only brand profiles for the mobile app. Published brands only,
 * resolved by slug.
 */
class BrandController extends Controller
{
    /**
     * Show a brand
     *
     * The public brand profile, resolved by slug. `description` comes back in the
     * request locale (Accept-Language).
     *
     * @unauthenticated
     */
    public function show(Brand $brand): JsonResponse
    {
        abort_unless((bool) $brand->is_published, 404);

        return response()->success(new BrandResource($brand));
    }
}
