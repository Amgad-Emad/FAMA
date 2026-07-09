<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Resources\Api\V1\CredibilityResource;
use Illuminate\Http\JsonResponse;

/**
 * @group Brand · Credibility
 *
 * @authenticated
 *
 * The brand's accrued credibility (read-only). Grows monotonically as deals
 * complete (the DealCompleted listener recalculates it); returns null until the
 * first completed deal.
 */
class CredibilityController extends BrandApiController
{
    /**
     * Get my credibility
     */
    public function show(): JsonResponse
    {
        $credibility = $this->brand()->credibility;

        return response()->success($credibility ? new CredibilityResource($credibility) : null);
    }
}
