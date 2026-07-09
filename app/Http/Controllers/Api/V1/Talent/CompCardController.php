<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Http\Requests\Api\V1\Talent\CompCardRequest;
use App\Http\Resources\Api\V1\CompCardResource;
use Illuminate\Http\JsonResponse;

/**
 * @group Talent · Comp card
 *
 * @authenticated
 *
 * The talent's model comp-card (stats) — a 1:1 record. `show` returns null when
 * the talent has not filled one yet; `update` upserts it.
 */
class CompCardController extends TalentApiController
{
    /**
     * Get my comp card
     */
    public function show(): JsonResponse
    {
        $card = $this->talent()->compCard;

        return response()->success($card ? new CompCardResource($card) : null);
    }

    /**
     * Create / update my comp card
     */
    public function update(CompCardRequest $request): JsonResponse
    {
        $card = $this->talent()->compCard()->updateOrCreate([], $request->validated());

        return response()->success(new CompCardResource($card), __('Comp card saved.'));
    }

    /**
     * Delete my comp card
     */
    public function destroy(): JsonResponse
    {
        $this->talent()->compCard()?->delete();

        return response()->success(null, __('Comp card removed.'));
    }
}
