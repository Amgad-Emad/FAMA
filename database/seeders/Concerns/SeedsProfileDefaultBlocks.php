<?php

namespace Database\Seeders\Concerns;

use App\Models\BlockType;
use App\Models\Talent;

/**
 * Appends the PROFILE-LEVEL default blocks (ADR-Z) to a seeded talent.
 *
 * The demo seeders build `profile_blocks` by hand rather than through
 * SeedBlocksForSkill, because they need control over positions and content. That
 * hand-rolled path reads `talent_types.default_blocks`, which only ever covers
 * SKILL blocks — so without this the Recognitions and Willing-to-explore
 * sections would exist in the catalog, exist in the data, and render nowhere.
 *
 * Shared rather than copied into each seeder so the rule stays in one place: a
 * block is profile-level because the CATALOG says so (`is_profile_default`),
 * never because a seeder remembered to list it.
 */
trait SeedsProfileDefaultBlocks
{
    /**
     * Give the talent one universal block per profile-default type, appended
     * after whatever universal blocks the caller already created. Idempotent.
     */
    protected function seedProfileDefaultBlocks(Talent $talent): void
    {
        $defaults = BlockType::query()
            ->where('is_profile_default', true)
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        if ($defaults->isEmpty()) {
            return;
        }

        $universal = $talent->profileBlocks()->whereNull('talent_type_id');
        $present = (clone $universal)->pluck('block_type_id')->all();
        $position = (clone $universal)->exists() ? ((int) (clone $universal)->max('position')) + 1 : 0;

        foreach ($defaults as $blockType) {
            if (in_array($blockType->id, $present, true)) {
                continue;
            }

            $talent->profileBlocks()->create([
                'block_type_id' => $blockType->id,
                'talent_type_id' => null,
                'title' => $blockType->getTranslations('name'),
                'position' => $position++,
                'is_visible' => true,
                'status' => 'visible',
                'layout' => $blockType->default_layout,
                'settings' => [],
                'content' => null,
            ]);
        }
    }
}
