<?php

namespace App\Services;

use App\Actions\SeedProfileBlocks;
use App\Models\Talent;
use App\Models\TalentType;
use InvalidArgumentException;

/**
 * Professions manager (talent-spec workflow #11). Add/remove talent types, mark
 * one primary, reorder. Adding a type merges its default blocks and seeds the
 * *missing* ones; the DB UNIQUE(talent_id, talent_type_id) plus an explicit guard
 * prevent duplicates.
 */
class ProfessionsService extends Service
{
    public function __construct(private readonly SeedProfileBlocks $seedProfileBlocks) {}

    /**
     * Link a profession to the talent, seed its missing blocks, and optionally
     * make it the primary (the first type is always primary).
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
            ($this->seedProfileBlocks)($talent);

            if ($primary || $talent->talentTypes()->count() === 1) {
                $this->setPrimary($talent, $type);
            }
        }, ['talent_id' => $talent->id, 'type' => $type->slug]);
    }

    /**
     * Unlink a profession from the talent.
     */
    public function removeType(Talent $talent, TalentType $type): void
    {
        $this->runInTransaction(
            fn () => $talent->talentTypes()->detach($type->id),
            ['talent_id' => $talent->id, 'type' => $type->slug],
        );
    }

    /**
     * Make one linked type the primary (clears the flag on the others).
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
     * Reorder the talent's professions to match the given type-id order.
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
