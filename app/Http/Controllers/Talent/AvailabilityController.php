<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\UpdateAvailabilityRequest;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Availability & travel settings (talent-spec) — availability status (state
 * machine) plus willing_to_travel / travel_regions / rate_tier.
 */
class AvailabilityController extends TalentController
{
    public function __construct(private readonly TalentProfileService $profile) {}

    public function index(): View
    {
        return view('talent.availability', ['talent' => $this->talent()]);
    }

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
