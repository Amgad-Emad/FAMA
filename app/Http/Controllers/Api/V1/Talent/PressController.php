<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Http\Requests\Talent\StorePressRequest;
use App\Http\Resources\PressResource;
use App\Models\PressFeature;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;

/**
 * @group Talent · Press
 *
 * @authenticated
 *
 * The talent's press features (external links).
 */
class PressController extends TalentApiController
{
    public function __construct(private readonly TalentProfileService $profile) {}

    /**
     * List my press features
     */
    public function index(): JsonResponse
    {
        $paginator = $this->talent()->pressFeatures()->orderBy('position')->paginate(15);

        return response()->paginated($paginator, PressResource::collection($paginator->getCollection()));
    }

    /**
     * Add a press feature
     */
    public function store(StorePressRequest $request): JsonResponse
    {
        $feature = $this->profile->addPressFeature($this->talent(), $request->validated());

        return response()->success(new PressResource($feature), __('Press feature added.'), status: 201);
    }

    /**
     * Remove a press feature
     */
    public function destroy(PressFeature $press): JsonResponse
    {
        $this->ensureOwns($press);
        $this->profile->removePressFeature($press);

        return response()->success(null, __('Press feature removed.'));
    }
}
