<?php

namespace App\Actions;

use App\Actions\Contracts\Action;
use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Talent;
use App\Models\TalentType;
use App\States\TalentProfile\Created;
use App\States\TalentProfile\Draft;
use Illuminate\Support\Collection;

/**
 * Seed ONE skill's `default_blocks` into that skill's scope (ADR-Q) — the block
 * seeding is now per-skill, not a global merge. Each new block is stamped with the
 * skill's `talent_type_id` and positioned within that skill's tab; dedupe is scoped
 * to (talent, skill, block_type), so a model-photographer gets its own gallery in
 * BOTH tabs. Idempotent within a scope: a block already present in this skill's tab
 * is skipped. A freshly-created profile moves Created → Draft once blocks exist.
 */
class SeedBlocksForSkill implements Action
{
    /**
     * @return Collection<int, ProfileBlock> Every block created this call —
     *   this skill's tab blocks AND, on the first call for a talent, the
     *   profile-level defaults (ADR-Z), which carry a NULL scope.
     */
    public function __invoke(Talent $talent, TalentType $skill): Collection
    {
        $keys = $skill->default_blocks ?? [];
        $blockTypes = BlockType::whereIn('key', $keys)->get()->keyBy('key');

        // Dedupe + position are scoped to this skill's tab.
        $scope = $talent->profileBlocks()->where('talent_type_id', $skill->id);
        $presentTypeIds = (clone $scope)->pluck('block_type_id')->all();
        $position = (clone $scope)->exists() ? ((int) (clone $scope)->max('position')) + 1 : 0;

        $created = new Collection;

        foreach ($keys as $key) {
            $blockType = $blockTypes->get($key);

            if ($blockType === null || in_array($blockType->id, $presentTypeIds, true)) {
                continue;
            }

            $created->push($talent->profileBlocks()->create([
                'block_type_id' => $blockType->id,
                'talent_type_id' => $skill->id,
                'title' => $blockType->getTranslations('name'),
                'position' => $position++,
                'is_visible' => true,
                'status' => 'visible',
                'layout' => $blockType->default_layout,
                'settings' => [],
                'content' => null,
            ]));

            $presentTypeIds[] = $blockType->id;
        }

        // Profile-level defaults ride along with the first skill (ADR-Z). They
        // describe the PERSON, not a discipline, so they are seeded once into
        // the universal region instead of once per skill tab.
        $created = $created->merge($this->seedProfileDefaults($talent));

        if ($talent->status->equals(Created::class)) {
            $talent->status->transitionTo(Draft::class);
        }

        return $created;
    }

    /**
     * Ensure the blocks flagged `is_profile_default` exist ONCE at
     * `talent_type_id = NULL`. Idempotent: a talent who already has one keeps
     * exactly one, so this is safe to run on every skill they add.
     *
     * Which blocks these are is DATA (`block_types.is_profile_default`), not a
     * list in this file — the catalog governs it, the same way it governs
     * eligibility (ADR-T).
     *
     * @return Collection<int, ProfileBlock>
     */
    private function seedProfileDefaults(Talent $talent): Collection
    {
        $defaults = BlockType::query()
            ->where('is_profile_default', true)
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        if ($defaults->isEmpty()) {
            return new Collection;
        }

        $universal = $talent->profileBlocks()->whereNull('talent_type_id');
        $presentTypeIds = (clone $universal)->pluck('block_type_id')->all();
        $position = (clone $universal)->exists() ? ((int) (clone $universal)->max('position')) + 1 : 0;

        $created = new Collection;

        foreach ($defaults as $blockType) {
            if (in_array($blockType->id, $presentTypeIds, true)) {
                continue;
            }

            $created->push($talent->profileBlocks()->create([
                'block_type_id' => $blockType->id,
                'talent_type_id' => null,
                'title' => $blockType->getTranslations('name'),
                'position' => $position++,
                'is_visible' => true,
                'status' => 'visible',
                'layout' => $blockType->default_layout,
                'settings' => [],
                'content' => null,
            ]));

            $presentTypeIds[] = $blockType->id;
        }

        return $created;
    }
}
