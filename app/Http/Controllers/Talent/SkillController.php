<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\ReorderRequest;
use App\Http\Requests\Talent\StoreSkillRequest;
use App\Http\Requests\Talent\UpdateSpecialitiesRequest;
use App\Models\TalentType;
use App\Services\SkillsService;
use Illuminate\Http\JsonResponse;

/**
 * Skills manager (talent-spec) — add/remove skills, set primary, reorder. Adding a
 * skill seeds its missing blocks (via SkillsService). Surfaced as a section inside
 * the Profile editor; every action returns the JSON envelope (no standalone page).
 */
class SkillController extends TalentController
{
    public function __construct(private readonly SkillsService $skills) {}

    public function data(): JsonResponse
    {
        return response()->success($this->payload());
    }

    public function store(StoreSkillRequest $request): JsonResponse
    {
        $type = TalentType::findOrFail($request->integer('talent_type_id'));
        $this->skills->addType($this->talent(), $type, $request->boolean('is_primary'));

        return response()->success($this->payload(), __('Skill added.'), status: 201);
    }

    public function primary(TalentType $type): JsonResponse
    {
        $this->skills->setPrimary($this->talent(), $type);

        return response()->success($this->payload(), __('Primary skill updated.'));
    }

    public function reorder(ReorderRequest $request): JsonResponse
    {
        $this->skills->reorderTypes($this->talent(), $request->orderedIds());

        return response()->success(null, __('Order saved.'));
    }

    /**
     * Replace the talent's speciality picks under one skill (ADR-Y). The picker
     * is per-skill, so this only ever touches that skill's claims.
     */
    public function specialities(UpdateSpecialitiesRequest $request, TalentType $type): JsonResponse
    {
        $this->skills->setSpecialities($this->talent(), $type, $request->specialityIds());

        return response()->success($this->payload(), __('Specialities saved.'));
    }

    public function destroy(TalentType $type): JsonResponse
    {
        $this->skills->removeType($this->talent(), $type);

        return response()->success($this->payload(), __('Skill removed.'));
    }

    /**
     * The skills editor payload — one home in SkillsService (shared with onboarding).
     *
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return $this->skills->editorPayload($this->talent());
    }
}
