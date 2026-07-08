<?php

namespace App\Http\Controllers\Admin;

use App\Models\BlockType;
use App\Models\TalentType;
use App\Services\ProfessionCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin profession/template catalog (Phase 3B UI). Visually edit a talent type's
 * default_blocks and add professions without code. Delegates to
 * ProfessionCatalogService; `can:manage-flows` gates the routes.
 */
class ProfessionController extends AdminController
{
    public function __construct(private readonly ProfessionCatalogService $catalog) {}

    public function index(): View
    {
        return view('admin.professions.index', [
            'blockTypes' => BlockType::query()->orderBy('key')->get(['key', 'name']),
        ]);
    }

    public function data(): JsonResponse
    {
        $types = TalentType::query()->orderBy('id')->get()->map(fn (TalentType $t) => [
            'id' => $t->id,
            'name' => $t->getTranslation('name', app()->getLocale()),
            'slug' => $t->slug,
            'category' => $t->category,
            'default_blocks' => $t->default_blocks ?? [],
        ]);

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

        $type = $this->catalog->addProfession($this->admin(), $data);

        return response()->success(['id' => $type->id, 'slug' => $type->slug], __('Profession added.'), status: 201);
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
