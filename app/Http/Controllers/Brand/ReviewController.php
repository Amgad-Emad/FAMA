<?php

namespace App\Http\Controllers\Brand;

use App\Http\Resources\BrandReviewResource;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Reviews received (brand-spec) — read-only. Only approved reviews are visible
 * to the brand; the brand can never edit them.
 */
class ReviewController extends BrandController
{
    public function index(): View
    {
        return view('brand.reviews');
    }

    public function data(): JsonResponse
    {
        $paginator = $this->brand()->brandReviews()
            ->where('is_approved', true)
            ->with('talent')
            ->latest()
            ->paginate(15);

        return response()->paginated($paginator, BrandReviewResource::collection($paginator->getCollection()));
    }
}
