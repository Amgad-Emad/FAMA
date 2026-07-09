<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Resources\BrandReviewResource;
use Illuminate\Http\JsonResponse;

/**
 * @group Brand · Reviews
 *
 * @authenticated
 *
 * Reviews received — read-only. Only approved reviews are visible; the brand can
 * never edit them.
 */
class ReviewController extends BrandApiController
{
    /**
     * List reviews received
     *
     * Paginated, approved-only, newest first.
     */
    public function index(): JsonResponse
    {
        $paginator = $this->brand()->brandReviews()
            ->where('is_approved', true)
            ->with('talent')
            ->latest()
            ->paginate(15);

        return response()->paginated($paginator, BrandReviewResource::collection($paginator->getCollection()));
    }
}
