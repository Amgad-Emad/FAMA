<?php

namespace App\Services;

use App\Actions\SeedBlocksForSkill;
use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Talent;
use App\Models\TalentType;
use App\States\Block\Hidden;
use App\States\Block\Visible;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The malleable block system's write side (talent-spec workflow #12), now
 * SCOPE-AWARE (ADR-Q). Every block lives in a scope: a skill's tab
 * (`talent_type_id`) or the universal / profile-level section (NULL). Add, fill,
 * show/hide and remove. (Talent-side reorder + move-between-scopes were removed
 * with the editor's Sections panel on 2026-11-23 — blocks keep their seeded
 * scope and order.)
 *
 * The picker ({@see availableBlockTypes()}) is per-scope: it lists only `is_active`
 * blocks the talent is eligible for **in that scope** (a `by_type`/`by_category`
 * block only in the tab of a skill it's gated to; universal blocks in any tab OR
 * the universal section) and omits non-repeatable blocks already present IN THAT
 * SCOPE. Rendering still resolves `block_type_id → block_types`, so a grandfathered
 * (deactivated) block still renders — only the picker filters on `is_active`.
 */
class ProfileBlockService extends Service
{
    public function __construct(private readonly SeedBlocksForSkill $seedBlocksForSkill) {}

    /**
     * Seed a new profile's blocks from every linked skill's defaults (per-skill).
     *
     * @return Collection<int, ProfileBlock>
     */
    public function seedFromDefaults(Talent $talent): Collection
    {
        return $this->runInTransaction(function () use ($talent): Collection {
            $created = new Collection;
            foreach ($talent->talentTypes as $skill) {
                $created = $created->concat(($this->seedBlocksForSkill)($talent, $skill));
            }

            return $created;
        }, ['talent_id' => $talent->id]);
    }

    /**
     * The block picker for a given scope (eligible, active, not-already-present in
     * that scope). `$scope` NULL = the universal / profile-level section.
     *
     * @return Collection<int, BlockType>
     */
    public function availableBlockTypes(Talent $talent, ?TalentType $scope = null): Collection
    {
        $presentInScope = $talent->profileBlocks()
            ->when($scope, fn ($q) => $q->where('talent_type_id', $scope->id), fn ($q) => $q->whereNull('talent_type_id'))
            ->pluck('block_type_id')
            ->all();

        return BlockType::query()
            ->where('is_active', true)
            ->with(['categories', 'talentTypes'])
            ->orderBy('position')
            ->get()
            ->filter(fn (BlockType $blockType): bool => $this->isEligibleForScope($blockType, $scope))
            ->reject(fn (BlockType $blockType): bool => ! $blockType->is_repeatable && in_array($blockType->id, $presentInScope, true))
            ->values();
    }

    /**
     * Whether a block type may be added to a given scope. In the universal section
     * (NULL scope) only universal blocks are allowed; in a skill's tab a block is
     * eligible if it is universal, or gated to that skill's category/type.
     */
    public function isEligibleForScope(BlockType $blockType, ?TalentType $scope): bool
    {
        if ($scope === null) {
            return $blockType->availability === 'universal';
        }

        return match ($blockType->availability) {
            'universal' => true,
            'by_category' => $blockType->categories->pluck('category')->contains($scope->category),
            'by_type' => $blockType->talentTypes->pluck('id')->contains($scope->id),
            default => false,
        };
    }

    /**
     * Add a block to a scope (must be in that scope's available set). Positioned at
     * the end of the scope.
     */
    public function addBlock(Talent $talent, BlockType $blockType, ?TalentType $scope = null): ProfileBlock
    {
        return $this->runInTransaction(function () use ($talent, $blockType, $scope): ProfileBlock {
            if (! $this->availableBlockTypes($talent, $scope)->contains('id', $blockType->id)) {
                throw new InvalidArgumentException("Block [{$blockType->key}] is not available in this scope.");
            }

            return $talent->profileBlocks()->create([
                'block_type_id' => $blockType->id,
                'talent_type_id' => $scope?->id,
                'title' => $blockType->getTranslations('name'),
                'position' => $this->nextPosition($talent, $scope?->id),
                'is_visible' => true,
                'status' => 'visible',
                'layout' => $blockType->default_layout,
                'settings' => [],
                'content' => null,
            ]);
        }, ['talent_id' => $talent->id, 'block_type' => $blockType->key, 'scope' => $scope?->id]);
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
     * Show or hide a block (Block state machine; idempotent if already there).
     */
    public function setVisibility(ProfileBlock $block, bool $visible): ProfileBlock
    {
        if (! $visible && self::isPinned($block)) {
            throw new InvalidArgumentException(__('Projects always shows on your profile while you have projects.'));
        }

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
        if (self::isPinned($block)) {
            throw new InvalidArgumentException(__('Projects is part of every skill tab and cannot be removed.'));
        }

        $this->runInTransaction(fn () => $block->delete(), ['block_id' => $block->id]);
    }

    /**
     * Whether a block is a fixed part of its skill tab (2026-11-23): the Projects
     * section of a skill scope. Every skill tab has one, it always shows while
     * the talent has work there, and it can't be hidden or removed. Profile-wide Projects (credits on unheld skills)
     * is not pinned.
     */
    public static function isPinned(ProfileBlock $block): bool
    {
        return $block->talent_type_id !== null && $block->blockType?->key === 'projects';
    }

    /**
     * The next position at the end of a scope (per talent_type_id; NULL = universal).
     */
    private function nextPosition(Talent $talent, ?int $scopeTypeId): int
    {
        $scope = $talent->profileBlocks()
            ->when($scopeTypeId, fn ($q) => $q->where('talent_type_id', $scopeTypeId), fn ($q) => $q->whereNull('talent_type_id'));

        return $scope->exists() ? ((int) $scope->max('position')) + 1 : 0;
    }
}
