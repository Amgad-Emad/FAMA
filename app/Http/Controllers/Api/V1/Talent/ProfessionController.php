<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Http\Requests\Talent\ReorderRequest;
use App\Http\Requests\Talent\StoreProfessionRequest;
use App\Http\Resources\TalentTypeResource;
use App\Models\TalentType;
use App\Services\ProfessionsService;
use Illuminate\Http\JsonResponse;

/**
 * @group Talent · Professions
 *
 * @authenticated
 *
 * The talent's profession(s): add/remove types, set primary, reorder. Adding a
 * type seeds its missing profile blocks (ProfessionsService).
 */
class ProfessionController extends TalentApiController
{
    public function __construct(private readonly ProfessionsService $professions) {}

    /**
     * List my professions
     *
     * The linked (ordered) professions and the ones still available to add.
     */
    public function index(): JsonResponse
    {
        return response()->success($this->payload());
    }

    /**
     * Add a profession
     */
    public function store(StoreProfessionRequest $request): JsonResponse
    {
        $type = TalentType::findOrFail($request->integer('talent_type_id'));
        $this->professions->addType($this->talent(), $type, $request->boolean('is_primary'));

        return response()->success($this->payload(), __('Profession added.'), status: 201);
    }

    /**
     * Set my primary profession
     */
    public function primary(TalentType $type): JsonResponse
    {
        $this->professions->setPrimary($this->talent(), $type);

        return response()->success($this->payload(), __('Primary profession updated.'));
    }

    /**
     * Reorder my professions
     */
    public function reorder(ReorderRequest $request): JsonResponse
    {
        $this->professions->reorderTypes($this->talent(), $request->orderedIds());

        return response()->success(null, __('Order saved.'));
    }

    /**
     * Remove a profession
     */
    public function destroy(TalentType $type): JsonResponse
    {
        $this->professions->removeType($this->talent(), $type);

        return response()->success($this->payload(), __('Profession removed.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $talent = $this->talent()->load('talentTypes');

        return [
            'linked' => TalentTypeResource::collection($talent->talentTypes),
            'available' => TalentTypeResource::collection(
                TalentType::whereNotIn('id', $talent->talentTypes->pluck('id'))->orderBy('id')->get()
            ),
        ];
    }
}
