<?php

namespace App\Services;

use App\Actions\SeedProfileBlocks;
use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Talent;
use App\States\Block\Hidden;
use App\States\Block\Visible;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The malleable block system's write side (talent-spec workflow #12). Add, fill,
 * reorder, show/hide and remove blocks. The block picker
 * ({@see availableBlockTypes()}) lists only `is_active` blocks the talent is
 * eligible for (universal, or gated to one of their categories/types) and omits
 * non-repeatable blocks already on the profile.
 *
 * Rendering resolves through `profile_blocks.block_type_id → block_types`, so a
 * block whose type was later deactivated (grandfathered) still renders — only the
 * picker filters on `is_active`.
 */
class ProfileBlockService extends Service
{
    public function __construct(private readonly SeedProfileBlocks $seedProfileBlocks) {}

    /**
     * Seed a new profile's blocks from its types' merged defaults.
     *
     * @return Collection<int, ProfileBlock>
     */
    public function seedFromDefaults(Talent $talent): Collection
    {
        return $this->runInTransaction(
            fn (): Collection => ($this->seedProfileBlocks)($talent),
            ['talent_id' => $talent->id],
        );
    }

    /**
     * The block picker for this talent (eligible, active, not-already-present).
     *
     * @return Collection<int, BlockType>
     */
    public function availableBlockTypes(Talent $talent): Collection
    {
        $talent->loadMissing('talentTypes', 'profileBlocks');

        $categories = $talent->talentTypes->pluck('category')->unique();
        $typeIds = $talent->talentTypes->pluck('id');
        $presentTypeIds = $talent->profileBlocks->pluck('block_type_id')->all();

        return BlockType::query()
            ->where('is_active', true)
            ->with(['categories', 'talentTypes'])
            ->orderBy('position')
            ->get()
            ->filter(fn (BlockType $blockType): bool => $this->isEligible($blockType, $categories, $typeIds))
            ->reject(fn (BlockType $blockType): bool => ! $blockType->is_repeatable && in_array($blockType->id, $presentTypeIds, true))
            ->values();
    }

    /**
     * Whether a block type is offered to a talent given their categories/types.
     *
     * @param  Collection<int, string>  $categories
     * @param  Collection<int, int>  $typeIds
     */
    public function isEligible(BlockType $blockType, Collection $categories, Collection $typeIds): bool
    {
        return match ($blockType->availability) {
            'universal' => true,
            'by_category' => $blockType->categories->pluck('category')->intersect($categories)->isNotEmpty(),
            'by_type' => $blockType->talentTypes->pluck('id')->intersect($typeIds)->isNotEmpty(),
            default => false,
        };
    }

    /**
     * Add a block to the profile (must be in the talent's available set).
     */
    public function addBlock(Talent $talent, BlockType $blockType): ProfileBlock
    {
        return $this->runInTransaction(function () use ($talent, $blockType): ProfileBlock {
            if (! $this->availableBlockTypes($talent)->contains('id', $blockType->id)) {
                throw new InvalidArgumentException("Block [{$blockType->key}] is not available to this talent.");
            }

            $position = $talent->profileBlocks()->exists()
                ? ((int) $talent->profileBlocks()->max('position')) + 1
                : 0;

            return $talent->profileBlocks()->create([
                'block_type_id' => $blockType->id,
                'title' => $blockType->getTranslations('name'),
                'position' => $position,
                'is_visible' => true,
                'status' => 'visible',
                'layout' => $blockType->default_layout,
                'settings' => [],
                'content' => null,
            ]);
        }, ['talent_id' => $talent->id, 'block_type' => $blockType->key]);
    }

    /**
     * Fill/edit a block's content (title/content/settings/layout only).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function fillBlock(ProfileBlock $block, array $attributes): ProfileBlock
    {
        return $this->runInTransaction(function () use ($block, $attributes): ProfileBlock {
            $block->fill(Arr::only($attributes, ['title', 'content', 'settings', 'layout']));
            $block->save();

            return $block;
        }, ['block_id' => $block->id]);
    }

    /**
     * Reorder a talent's blocks to match the given id order (0-indexed positions).
     *
     * @param  list<int>  $orderedBlockIds
     */
    public function reorder(Talent $talent, array $orderedBlockIds): void
    {
        $this->runInTransaction(function () use ($talent, $orderedBlockIds): void {
            $owned = $talent->profileBlocks()->pluck('id')->all();

            foreach ($orderedBlockIds as $index => $id) {
                if (! in_array($id, $owned, true)) {
                    throw new InvalidArgumentException("Block [{$id}] does not belong to this talent.");
                }

                $talent->profileBlocks()->whereKey($id)->update(['position' => $index]);
            }
        }, ['talent_id' => $talent->id]);
    }

    /**
     * Show or hide a block (Block state machine; idempotent if already there).
     */
    public function setVisibility(ProfileBlock $block, bool $visible): ProfileBlock
    {
        return $this->runInTransaction(function () use ($block, $visible): ProfileBlock {
            $target = $visible ? Visible::class : Hidden::class;

            if ($block->status->canTransitionTo($target)) {
                $block->status->transitionTo($target);
            }

            return $block->refresh();
        }, ['block_id' => $block->id]);
    }

    /**
     * Remove a block from the profile.
     */
    public function removeBlock(ProfileBlock $block): void
    {
        $this->runInTransaction(fn () => $block->delete(), ['block_id' => $block->id]);
    }
}
