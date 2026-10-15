<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use App\Support\Locations;
use Illuminate\Http\JsonResponse;

/**
 * Feeds the City drop list: the cities of one country, largest first. Public
 * and static, so the browser may cache it for a day.
 */
class LocationController extends Controller
{
    /** GET /locations/{country}/cities — {data: [city names]}; unknown code → 404. */
    public function cities(string $country): JsonResponse
    {
        $cities = Locations::cities($country);
        abort_if($cities === [], 404);

        return ApiResponse::success($cities)->header('Cache-Control', 'public, max-age=86400');
    }
}
