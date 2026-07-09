<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Http\Requests\Talent\StoreAffiliationRequest;
use App\Http\Resources\AffiliationResource;
use App\Models\AgencyAffiliation;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;

/**
 * @group Talent · Affiliations
 *
 * @authenticated
 *
 * The talent's agency representation — add/update, mark past (Affiliation state
 * machine), remove.
 */
class AffiliationController extends TalentApiController
{
    public function __construct(private readonly TalentProfileService $profile) {}

    /**
     * List my affiliations
     */
    public function index(): JsonResponse
    {
        $paginator = $this->talent()->agencyAffiliations()->latest()->paginate(15);

        return response()->paginated($paginator, AffiliationResource::collection($paginator->getCollection()));
    }

    /**
     * Add an affiliation
     */
    public function store(StoreAffiliationRequest $request): JsonResponse
    {
        $affiliation = $this->profile->addAffiliation($this->talent(), $request->validated());

        return response()->success(new AffiliationResource($affiliation), __('Affiliation added.'), status: 201);
    }

    /**
     * Update an affiliation
     */
    public function update(StoreAffiliationRequest $request, AgencyAffiliation $affiliation): JsonResponse
    {
        $this->ensureOwns($affiliation);
        $affiliation->update($request->validated());

        return response()->success(new AffiliationResource($affiliation), __('Affiliation updated.'));
    }

    /**
     * End an affiliation
     */
    public function end(AgencyAffiliation $affiliation): JsonResponse
    {
        $this->ensureOwns($affiliation);

        return response()->success(new AffiliationResource($this->profile->endAffiliation($affiliation)), __('Affiliation ended.'));
    }

    /**
     * Remove an affiliation
     */
    public function destroy(AgencyAffiliation $affiliation): JsonResponse
    {
        $this->ensureOwns($affiliation);
        $this->profile->removeAffiliation($affiliation);

        return response()->success(null, __('Affiliation removed.'));
    }
}
