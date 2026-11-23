<?php

namespace App\Support;

use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Talent;
use Illuminate\Support\Collection;

/**
 * THE list of sections a public profile renders for one scope (a skill tab, or
 * the profile-wide region when $typeId is null). Used by the profile page and by
 * the lazy tab endpoint so they always agree.
 *
 * Rules: the `hero` block is retired (ADR-O); reviews hide while
 * features.reviews is off; and PROJECTS ALWAYS SHOW while the scope has work
 * (2026-11-23) — when a scope has projects but no visible Projects block (a
 * legacy profile, or one built by hand), a Projects section is added at the end.
 */
class ProfileSections
{
    /**
     * The visible sections of a scope, in position order.
     *
     * Expects `profileBlocks.blockType` and the portfolio relations
     * (projects.talentTypes, creditedProjects) to be eager-loaded.
     *
     * @return Collection<int, ProfileBlock>
     */
    public static function forScope(Talent $talent, ?int $typeId): Collection
    {
        $reviewsOn = Features::enabled('reviews');

        $blocks = $talent->profileBlocks
            ->filter(fn (ProfileBlock $b) => ($b->talent_type_id === null ? null : (int) $b->talent_type_id) === $typeId)
            ->filter(fn (ProfileBlock $b) => $b->blockType->key !== 'hero')
            ->filter(fn (ProfileBlock $b) => $reviewsOn || $b->blockType->key !== 'reviews')
            ->sortBy('position')
            ->values();

        $hasProjectsBlock = $blocks->contains(fn (ProfileBlock $b) => $b->blockType->key === 'projects');

        if (! $hasProjectsBlock && $talent->portfolioProjects($typeId)->isNotEmpty()) {
            $type = BlockType::query()->where('key', 'projects')->first();
            if ($type !== null) {
                $blocks->push(self::projectsBlock($talent, $type, $typeId));
            }
        }

        return $blocks;
    }

    /**
     * An unsaved Projects block for a scope — rendered, never persisted.
     */
    private static function projectsBlock(Talent $talent, BlockType $type, ?int $typeId): ProfileBlock
    {
        $block = (new ProfileBlock)->forceFill([
            'talent_id' => $talent->id,
            'block_type_id' => $type->id,
            'talent_type_id' => $typeId,
            'is_visible' => true,
            'position' => PHP_INT_MAX,
        ]);
        $block->setRelation('blockType', $type);

        return $block;
    }
}
