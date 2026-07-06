<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\ReorderRequest;
use App\Http\Requests\Talent\StoreProfessionRequest;
use App\Http\Resources\TalentTypeResource;
use App\Models\TalentType;
use App\Services\ProfessionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Professions manager (talent-spec) — add/remove types, set primary, reorder.
 * Adding a type seeds its missing blocks (via ProfessionsService).
 */
class ProfessionController extends TalentController
{
    public function __construct(private readonly ProfessionsService $professions) {}

    public function index(): View
    {
        return view('talent.professions', $this->payload());
    }

    public function data(): JsonResponse
    {
        return response()->success($this->payload());
    }

    public function store(StoreProfessionRequest $request): JsonResponse
    {
        $type = TalentType::findOrFail($request->integer('talent_type_id'));
        $this->professions->addType($this->talent(), $type, $request->boolean('is_primary'));

        return response()->success($this->payload(), __('Profession added.'), status: 201);
    }

    public function primary(TalentType $type): JsonResponse
    {
        $this->professions->setPrimary($this->talent(), $type);

        return response()->success($this->payload(), __('Primary profession updated.'));
    }

    public function reorder(ReorderRequest $request): JsonResponse
    {
        $this->professions->reorderTypes($this->talent(), $request->orderedIds());

        return response()->success(null, __('Order saved.'));
    }

    public function destroy(TalentType $type): JsonResponse
    {
        $this->professions->removeType($this->talent(), $type);

        return response()->success($this->payload(), __('Profession removed.'));
    }

    /**
     * The linked (ordered) professions and the ones still available to add.
     *
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
