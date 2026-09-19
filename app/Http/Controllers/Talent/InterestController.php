<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\ReorderRequest;
use App\Http\Requests\Talent\StoreInterestRequest;
use App\Http\Resources\TalentInterestResource;
use App\Models\Speciality;
use App\Models\TalentInterest;
use App\Models\TalentType;
use App\Services\TalentInterestService;
use Illuminate\Http\JsonResponse;

/**
 * "Willing to explore" (ADR-Z) — a section inside the Profile editor, not a page
 * of its own. Every action returns the JSON envelope; all writes go through
 * TalentInterestService.
 */
class InterestController extends TalentController
{
    public function __construct(private readonly TalentInterestService $interests) {}

    public function data(): JsonResponse
    {
        return response()->success($this->payload());
    }

    public function store(StoreInterestRequest $request): JsonResponse
    {
        $this->interests->create($this->talent(), $request->validated());

        return response()->success($this->payload(), __('Direction added.'), status: 201);
    }

    public function update(StoreInterestRequest $request, TalentInterest $interest): JsonResponse
    {
        $this->ensureOwns($interest);
        $this->interests->update($interest, $request->validated());

        return response()->success($this->payload(), __('Direction saved.'));
    }

    public function destroy(TalentInterest $interest): JsonResponse
    {
        $this->ensureOwns($interest);
        $this->interests->delete($interest);

        return response()->success($this->payload(), __('Direction removed.'));
    }

    public function reorder(ReorderRequest $request): JsonResponse
    {
        $this->interests->reorder($this->talent(), $request->orderedIds());

        return response()->success(null, __('Order saved.'));
    }

    /**
     * The talent's directions plus the full pickable vocabulary.
     *
     * The skill list is EVERY skill, not the talent's own — the section exists
     * to name directions they do not yet work in (ADR-Z).
     *
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $talent = $this->talent()->load(['interests.talentType', 'interests.speciality']);

        $specialities = Speciality::query()
            ->selectable()
            ->orderBy('position')->orderBy('id')
            ->get()
            ->groupBy('talent_type_id');

        return [
            'interests' => TalentInterestResource::collection($talent->interests),
            'skills' => TalentType::query()->orderBy('id')->get()->map(fn (TalentType $type) => [
                'id' => (int) $type->id,
                'slug' => $type->slug,
                'name' => $type->getTranslation('name', app()->getLocale(), false) ?: $type->getTranslation('name', 'en', false),
                'icon' => $type->icon,
                'specialities' => $specialities->get($type->id, collect())->map(fn (Speciality $s) => [
                    'id' => (int) $s->id,
                    'name' => $s->getTranslation('name', app()->getLocale(), false) ?: $s->getTranslation('name', 'en', false),
                ])->values(),
            ])->values(),
        ];
    }
}
