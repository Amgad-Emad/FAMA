<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\SpecialityResource;
use App\Models\BlockType;
use App\Models\Speciality;
use App\Models\TalentType;
use App\Services\ProfileBlockService;
use App\Services\SkillCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin skills template manager (the preselection side of the block governance
 * split). Adds skills without code and edits each skill's `default_blocks` —
 * PRESELECTION + ORDER only, choosing among the blocks the Block Catalog
 * Manager makes ELIGIBLE for that skill (universal + category/type gates).
 * Delegates to SkillCatalogService; `can:manage-flows` gates the routes.
 */
class SkillController extends AdminController
{
    public function __construct(private readonly SkillCatalogService $catalog) {}

    public function index(): View
    {
        return view('admin.skills.index');
    }

    /**
     * Skills + their block menus. Per skill: the ELIGIBLE blocks (active +
     * allowed by the catalog's availability gates — the only ones offerable),
     * the ordered preselection, and any preselected key that has since become
     * ineligible (surfaced so the admin can remove it). Gate relations are
     * loaded once for the whole catalog — no per-skill queries.
     */
    public function data(ProfileBlockService $blocks): JsonResponse
    {
        $blockTypes = BlockType::query()->with(['categories', 'talentTypes:talent_types.id'])->orderBy('position')->orderBy('key')->get();

        $types = TalentType::query()->with('specialities')->orderBy('id')->get()->map(function (TalentType $t) use ($blocks, $blockTypes) {
            $eligible = $blockTypes
                ->filter(fn (BlockType $b) => $b->is_active && $blocks->isEligibleForScope($b, $t))
                ->values();
            $eligibleKeys = $eligible->pluck('key');

            return [
                'id' => $t->id,
                'name' => $t->getTranslation('name', app()->getLocale()),
                'slug' => $t->slug,
                'category' => $t->category,
                'default_blocks' => $t->default_blocks ?? [],
                'eligible_blocks' => $eligible->map(fn (BlockType $b) => [
                    'key' => $b->key,
                    'name' => $b->getTranslation('name', app()->getLocale()),
                ])->values(),
                'invalid_blocks' => collect($t->default_blocks ?? [])->reject(fn (string $k) => $eligibleKeys->contains($k))->values(),
                // The controlled vocabulary under this skill (ADR-Y), retired
                // entries included — the admin governs both states from here.
                'specialities' => SpecialityResource::collection($t->specialities),
            ];
        });

        return response()->success(['types' => $types]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ar' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'in:model,crew,creative'],
            'default_blocks' => ['array'],
            'default_blocks.*' => ['string'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);

        $type = $this->catalog->addSkill($this->admin(), $data);

        return response()->success(['id' => $type->id, 'slug' => $type->slug], __('Skill added.'), status: 201);
    }

    public function updateBlocks(Request $request, TalentType $type): JsonResponse
    {
        $data = $request->validate([
            'default_blocks' => ['required', 'array'],
            'default_blocks.*' => ['string'],
        ]);

        $this->catalog->updateDefaultBlocks($this->admin(), $type, $data['default_blocks']);

        return response()->success(null, __('Default blocks updated.'));
    }

    /**
     * Add an entry to the controlled vocabulary under one skill (ADR-Y).
     */
    public function storeSpeciality(Request $request, TalentType $type): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255'],
            'name.ar' => ['nullable', 'string', 'max:255'],
            'is_regional' => ['boolean'],
            'description' => ['nullable', 'array'],
        ]);

        $speciality = $this->catalog->addSpeciality($this->admin(), $type, $data);

        return response()->success(
            ['speciality' => new SpecialityResource($speciality)],
            __('Speciality added.'),
            status: 201,
        );
    }

    /**
     * Edit an entry's copy or regional flag. The slug is locked (see the service).
     */
    public function updateSpeciality(Request $request, Speciality $speciality): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'array'],
            'name.en' => ['required_with:name', 'string', 'max:255'],
            'name.ar' => ['nullable', 'string', 'max:255'],
            'is_regional' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'array'],
        ]);

        $speciality = $this->catalog->updateSpeciality($this->admin(), $speciality, $data);

        return response()->success(['speciality' => new SpecialityResource($speciality)], __('Speciality updated.'));
    }

    /**
     * Retire or reinstate an entry. Retiring hides it from every picker and
     * leaves existing claims intact — it is never a delete.
     */
    public function toggleSpeciality(Request $request, Speciality $speciality): JsonResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        $speciality = $this->catalog->setSpecialityActive($this->admin(), $speciality, $data['is_active']);

        return response()->success(
            ['speciality' => new SpecialityResource($speciality)],
            $speciality->is_active ? __('Speciality reinstated.') : __('Speciality retired.'),
        );
    }

    /**
     * Persist the editorial order of a skill's specialities.
     */
    public function reorderSpecialities(Request $request, TalentType $type): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $this->catalog->reorderSpecialities($this->admin(), $type, $data['ids']);

        return response()->success(null, __('Order saved.'));
    }
}
