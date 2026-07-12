<?php

namespace App\Services;

use App\Actions\SeedBlocksForSkill;
use App\Models\PortfolioItem;
use App\Models\Talent;
use App\Models\TalentType;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Skills manager (talent-spec workflow #11). Add/remove skills, mark one primary,
 * reorder. Adding a skill seeds THAT skill's default blocks into its own tab
 * (per-skill, ADR-Q). Removing a skill deletes its tab's blocks but preserves the
 * underlying content (gallery items are un-linked, projects are un-scoped). Skills
 * persist through the `talent_types` catalog (see schema-master).
 */
class SkillsService extends Service
{
    public function __construct(private readonly SeedBlocksForSkill $seedBlocksForSkill) {}

    /**
     * Link a skill to the talent, seed its own tab's default blocks, and optionally
     * make it the primary (the first skill is always primary).
     */
    public function addType(Talent $talent, TalentType $type, bool $primary = false): void
    {
        $this->runInTransaction(function () use ($talent, $type, $primary): void {
            if ($talent->talentTypes()->whereKey($type->id)->exists()) {
                throw new InvalidArgumentException("Talent already works as [{$type->slug}].");
            }

            $position = (int) $talent->talentTypes()->newPivotStatement()
                ->where('talent_id', $talent->id)->max('position');

            $talent->talentTypes()->attach($type->id, [
                'is_primary' => false,
                'position' => $talent->talentTypes()->exists() ? $position + 1 : 0,
            ]);

            $talent->load('talentTypes');
            ($this->seedBlocksForSkill)($talent, $type);

            if ($primary || $talent->talentTypes()->count() === 1) {
                $this->setPrimary($talent, $type);
            }
        }, ['talent_id' => $talent->id, 'type' => $type->slug]);
    }

    /**
     * Unlink a skill from the talent. Deletes that skill's tab blocks but PRESERVES
     * content (ADR-Q): gallery items in those blocks lose their `block_id` and the
     * skill's projects are un-scoped (`talent_type_id` → NULL). The removal is
     * logged (the UI requires an explicit confirmation first).
     */
    public function removeType(Talent $talent, TalentType $type): void
    {
        $this->runInTransaction(function () use ($talent, $type): void {
            // Preserve project content — un-scope this skill's projects.
            $talent->projects()->where('talent_type_id', $type->id)->update(['talent_type_id' => null]);

            // Preserve gallery content — detach items from this skill's blocks, then
            // delete the blocks (the FK is nullOnDelete; this is explicit + logged).
            $blockIds = $talent->profileBlocks()->where('talent_type_id', $type->id)->pluck('id');
            if ($blockIds->isNotEmpty()) {
                PortfolioItem::whereIn('block_id', $blockIds)->update(['block_id' => null]);
                $talent->profileBlocks()->whereKey($blockIds)->delete();
            }

            $talent->talentTypes()->detach($type->id);

            Log::channel('app')->info('Talent removed a skill (content preserved).', [
                'talent_id' => $talent->id,
                'talent_type_id' => $type->id,
                'skill' => $type->slug,
                'blocks_deleted' => $blockIds->count(),
            ]);
        }, ['talent_id' => $talent->id, 'type' => $type->slug]);
    }

    /**
     * Make one linked skill the primary (clears the flag on the others).
     */
    public function setPrimary(Talent $talent, TalentType $type): void
    {
        $this->runInTransaction(function () use ($talent, $type): void {
            if (! $talent->talentTypes()->whereKey($type->id)->exists()) {
                throw new InvalidArgumentException("Talent does not work as [{$type->slug}].");
            }

            $talent->talentTypes()->newPivotStatement()
                ->where('talent_id', $talent->id)->update(['is_primary' => false]);

            $talent->talentTypes()->updateExistingPivot($type->id, ['is_primary' => true]);
        }, ['talent_id' => $talent->id, 'type' => $type->slug]);
    }

    /**
     * Reorder the talent's skills to match the given type-id order.
     *
     * @param  list<int>  $orderedTypeIds
     */
    public function reorderTypes(Talent $talent, array $orderedTypeIds): void
    {
        $this->runInTransaction(function () use ($talent, $orderedTypeIds): void {
            $owned = $talent->talentTypes()->pluck('talent_types.id')->all();

            foreach ($orderedTypeIds as $index => $id) {
                if (! in_array($id, $owned, true)) {
                    throw new InvalidArgumentException("Type [{$id}] is not linked to this talent.");
                }

                $talent->talentTypes()->updateExistingPivot($id, ['position' => $index]);
            }
        }, ['talent_id' => $talent->id]);
    }
}
