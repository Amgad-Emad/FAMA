<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\StoreAffiliationRequest;
use App\Http\Resources\AffiliationResource;
use App\Models\AgencyAffiliation;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Affiliations manager (talent-spec) — agency representation; add/update, mark
 * past (Affiliation state machine), remove. Press is managed on the same page
 * via PressController.
 */
class AffiliationController extends TalentController
{
    public function __construct(private readonly TalentProfileService $profile) {}

    public function index(): View
    {
        return view('talent.affiliations');
    }

    public function data(): JsonResponse
    {
        $paginator = $this->talent()->agencyAffiliations()->latest()->paginate(15);

        return response()->paginated($paginator, AffiliationResource::collection($paginator->getCollection()));
    }

    public function store(StoreAffiliationRequest $request): JsonResponse
    {
        $affiliation = $this->profile->addAffiliation($this->talent(), $request->validated());

        return response()->success(new AffiliationResource($affiliation), __('Affiliation added.'), status: 201);
    }

    public function update(StoreAffiliationRequest $request, AgencyAffiliation $affiliation): JsonResponse
    {
        $this->ensureOwns($affiliation);
        $affiliation->update($request->validated());

        return response()->success(new AffiliationResource($affiliation), __('Affiliation updated.'));
    }

    public function end(AgencyAffiliation $affiliation): JsonResponse
    {
        $this->ensureOwns($affiliation);

        return response()->success(new AffiliationResource($this->profile->endAffiliation($affiliation)), __('Affiliation ended.'));
    }

    public function destroy(AgencyAffiliation $affiliation): JsonResponse
    {
        $this->ensureOwns($affiliation);
        $this->profile->removeAffiliation($affiliation);

        return response()->success(null, __('Affiliation removed.'));
    }
}
