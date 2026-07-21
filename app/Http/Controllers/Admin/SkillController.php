<?php

namespace App\Http\Controllers\Admin;

use App\Models\BlockType;
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

        $types = TalentType::query()->orderBy('id')->get()->map(function (TalentType $t) use ($blocks, $blockTypes) {
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
}
