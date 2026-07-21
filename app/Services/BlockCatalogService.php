<?php

namespace App\Services;

use App\Models\BlockType;
use App\Models\User;
use InvalidArgumentException;

/**
 * Admin block-type catalog governance (the eligibility side of the block
 * governance split): which blocks EXIST, WHO can use them (`availability` +
 * the block_type_category / block_type_talent_type gates), and their config
 * (is_active / is_repeatable / default_layout / content_source /
 * settings_schema). Preselection + order per skill lives with the Skills
 * template manager (SkillCatalogService), which may only choose among blocks
 * this catalog makes eligible.
 *
 * Grandfathering: deactivating a type or narrowing its eligibility never
 * touches existing profile_blocks — they keep rendering; the type just stops
 * being offered for NEW placements. Deletion is deliberately not offered.
 *
 * Gated on `manage-blocks`; every mutation is transactional + activity-logged.
 */
class BlockCatalogService extends AdminService
{
    /**
     * Create a block type with its eligibility gates.
     *
     * @param  array<string, mixed>  $data
     */
    public function createBlockType(User $admin, array $data): BlockType
    {
        $this->authorizePermission($admin, 'manage-blocks');

        return $this->runInTransaction(function () use ($admin, $data): BlockType {
            $blockType = BlockType::create([
                'key' => $data['key'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'icon' => $data['icon'] ?? null,
                'availability' => $data['availability'],
                'content_source' => $data['content_source'] ?? 'inline',
                'default_layout' => $data['default_layout'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_repeatable' => $data['is_repeatable'] ?? false,
                'position' => $data['position'] ?? (int) BlockType::max('position') + 1,
                'settings_schema' => $data['settings_schema'] ?? null,
            ]);

            $this->syncGates($blockType, $data);
            $this->record($admin, $blockType, 'catalog', 'block_type.created', ['key' => $blockType->key]);

            return $blockType->refresh();
        }, ['key' => $data['key'] ?? null]);
    }

    /**
     * Update a block type's meta, config and eligibility gates.
     *
     * Guard rails: `key` and `content_source` are structural — profile_blocks
     * reference the key and content reads depend on the source — so both are
     * immutable once the type is in use. Narrowing availability only stops NEW
     * placements (existing rows are grandfathered); the response carries
     * `in_use_count` so the UI can warn.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBlockType(User $admin, BlockType $blockType, array $data): BlockType
    {
        $this->authorizePermission($admin, 'manage-blocks');

        $inUse = $blockType->profileBlocks()->exists();
        if ($inUse && array_key_exists('key', $data) && $data['key'] !== $blockType->key) {
            throw new InvalidArgumentException(__('The key cannot change once talents use this block.'));
        }
        if ($inUse && array_key_exists('content_source', $data) && $data['content_source'] !== $blockType->content_source) {
            throw new InvalidArgumentException(__('The content source cannot change once talents use this block.'));
        }

        return $this->runInTransaction(function () use ($admin, $blockType, $data): BlockType {
            $from = $blockType->only(['key', 'availability', 'content_source', 'is_active', 'is_repeatable', 'default_layout']);

            $blockType->update(collect($data)->only([
                'key', 'name', 'description', 'icon', 'availability', 'content_source',
                'default_layout', 'is_active', 'is_repeatable', 'settings_schema',
            ])->all());

            $this->syncGates($blockType, $data);
            $this->record($admin, $blockType, 'catalog', 'block_type.updated', [
                'from' => $from,
                'to' => $blockType->only(array_keys($from)),
            ]);

            return $blockType->refresh();
        }, ['block_type_id' => $blockType->getKey()]);
    }

    /**
     * Flip `is_active` (platform-wide on/off). Existing profile_blocks are
     * grandfathered — only NEW placements stop being offered.
     */
    public function toggleActive(User $admin, BlockType $blockType): BlockType
    {
        $this->authorizePermission($admin, 'manage-blocks');

        return $this->runInTransaction(function () use ($admin, $blockType): BlockType {
            $blockType->update(['is_active' => ! $blockType->is_active]);
            $this->record($admin, $blockType, 'catalog', $blockType->is_active ? 'block_type.activated' : 'block_type.deactivated', [
                'key' => $blockType->key,
            ]);

            return $blockType->refresh();
        }, ['block_type_id' => $blockType->getKey()]);
    }

    /**
     * Sync the eligibility gate rows to match `availability`: category gates for
     * by_category, skill gates for by_type, none for universal. Stale gates from
     * a previous availability are removed (eligibility reads only follow the
     * active mode, but leftovers would mislead the next editor).
     *
     * @param  array<string, mixed>  $data
     */
    private function syncGates(BlockType $blockType, array $data): void
    {
        $categories = $blockType->availability === 'by_category' ? array_values($data['categories'] ?? []) : [];
        $blockType->categories()->whereNotIn('category', $categories)->delete();
        foreach ($categories as $category) {
            $blockType->categories()->firstOrCreate(['category' => $category]);
        }

        $blockType->talentTypes()->sync($blockType->availability === 'by_type' ? array_values($data['talent_type_ids'] ?? []) : []);
    }
}
