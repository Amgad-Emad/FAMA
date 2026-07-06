<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\StorePressRequest;
use App\Http\Resources\PressResource;
use App\Models\PressFeature;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;

/**
 * Press list management (talent-spec) — surfaced on the affiliations page.
 */
class PressController extends TalentController
{
    public function __construct(private readonly TalentProfileService $profile) {}

    public function data(): JsonResponse
    {
        $paginator = $this->talent()->pressFeatures()->orderBy('position')->paginate(15);

        return response()->paginated($paginator, PressResource::collection($paginator->getCollection()));
    }

    public function store(StorePressRequest $request): JsonResponse
    {
        $feature = $this->profile->addPressFeature($this->talent(), $request->validated());

        return response()->success(new PressResource($feature), __('Press feature added.'), status: 201);
    }

    public function destroy(PressFeature $press): JsonResponse
    {
        $this->ensureOwns($press);
        $this->profile->removePressFeature($press);

        return response()->success(null, __('Press feature removed.'));
    }
}
