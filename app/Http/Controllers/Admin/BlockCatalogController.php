<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreBlockTypeRequest;
use App\Http\Requests\Admin\UpdateBlockTypeRequest;
use App\Models\BlockType;
use App\Models\TalentType;
use App\Services\BlockCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Admin block catalog manager — the eligibility side of the block governance
 * split: which block types exist, who can use them (availability + gates), and
 * their config. Preselection/order per skill lives on /admin/skills.
 * `can:manage-blocks` gates the routes; BlockCatalogService re-authorizes and
 * audits every mutation.
 */
class BlockCatalogController extends AdminController
{
    public function __construct(private readonly BlockCatalogService $catalog) {}

    /**
     * The catalog shell. Skills are passed for the by_type gate picker.
     */
    public function index(): View
    {
        return view('admin.blocks.index', [
            'talentTypes' => TalentType::query()->orderBy('id')->get(['id', 'name', 'slug'])
                ->map(fn (TalentType $t) => ['id' => $t->id, 'name' => $t->getTranslation('name', app()->getLocale())])
                ->values(),
        ]);
    }

    /**
     * Paginated catalog rows (JSON envelope). Gates + usage are eager-loaded /
     * counted so the guard-rail UI renders without N+1.
     */
    public function data(): JsonResponse
    {
        $paginator = BlockType::query()
            ->with(['categories', 'talentTypes:id'])
            ->withCount('profileBlocks')
            ->orderBy('position')->orderBy('key')
            ->paginate(30);

        return response()->paginated($paginator, $paginator->getCollection()->map(fn (BlockType $b) => [
            'id' => $b->id,
            'key' => $b->key,
            'name' => ['en' => $b->getTranslation('name', 'en'), 'ar' => $b->getTranslation('name', 'ar')],
            'description' => ['en' => $b->getTranslation('description', 'en'), 'ar' => $b->getTranslation('description', 'ar')],
            'icon' => $b->icon,
            'availability' => $b->availability,
            'categories' => $b->categories->pluck('category')->values(),
            'talent_type_ids' => $b->talentTypes->pluck('id')->values(),
            'content_source' => $b->content_source,
            'default_layout' => $b->default_layout,
            'is_active' => (bool) $b->is_active,
            'is_repeatable' => (bool) $b->is_repeatable,
            'settings_schema' => $b->settings_schema,
            'in_use_count' => $b->profile_blocks_count,
        ]));
    }

    /**
     * Create a block type (with its eligibility gates).
     */
    public function store(StoreBlockTypeRequest $request): JsonResponse
    {
        $blockType = $this->catalog->createBlockType($this->admin(), $this->payload($request->validated()));

        return response()->success(['id' => $blockType->id, 'key' => $blockType->key], __('Block type created.'), status: 201);
    }

    /**
     * Edit a block type. Structural guard rails (key / content_source once in
     * use) surface as 422 from the service.
     */
    public function update(UpdateBlockTypeRequest $request, BlockType $blockType): JsonResponse
    {
        $blockType = $this->catalog->updateBlockType($this->admin(), $blockType, $this->payload($request->validated()));

        return response()->success(['id' => $blockType->id], __('Block type updated.'));
    }

    /**
     * Retire / reinstate a block type (flips `is_active`; grandfathered).
     */
    public function toggle(BlockType $blockType): JsonResponse
    {
        $blockType = $this->catalog->toggleActive($this->admin(), $blockType);

        return response()->success(
            ['id' => $blockType->id, 'is_active' => (bool) $blockType->is_active],
            $blockType->is_active ? __('Block type activated.') : __('Block type deactivated.'),
        );
    }

    /**
     * The editor posts settings_schema as a raw JSON string (validated
     * well-formed by the Form Request); decode it for storage.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        if (array_key_exists('settings_schema', $data)) {
            $data['settings_schema'] = filled($data['settings_schema']) ? json_decode($data['settings_schema'], true) : null;
        }

        return $data;
    }
}
