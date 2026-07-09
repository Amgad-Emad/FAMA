<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Http\Requests\Talent\UpdateAvailabilityRequest;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;

/**
 * @group Talent · Availability
 *
 * @authenticated
 *
 * Availability status (state machine) plus travel preferences and rate tier.
 */
class AvailabilityController extends TalentApiController
{
    public function __construct(private readonly TalentProfileService $profile) {}

    /**
     * Get my availability
     */
    public function show(): JsonResponse
    {
        $talent = $this->talent();

        return response()->success([
            'availability_status' => $talent->availability_status->getValue(),
            'willing_to_travel' => (bool) $talent->willing_to_travel,
            'travel_regions' => $talent->travel_regions,
            'rate_tier' => $talent->rate_tier,
        ]);
    }

    /**
     * Update my availability & travel
     */
    public function update(UpdateAvailabilityRequest $request): JsonResponse
    {
        $talent = $this->talent();

        $this->profile->setAvailability($talent, $request->input('availability_status'));
        $this->profile->updateCore($talent, $request->only(['willing_to_travel', 'travel_regions', 'rate_tier']));
        $talent->refresh();

        return response()->success([
            'availability_status' => $talent->availability_status->getValue(),
            'willing_to_travel' => (bool) $talent->willing_to_travel,
            'travel_regions' => $talent->travel_regions,
            'rate_tier' => $talent->rate_tier,
        ], __('Availability updated.'));
    }
}
