<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * No separately chosen primary skill any more (fama-ui, 2026-10-12): the skill
 * order IS the profile's tab order and the first skill opens first. This
 * rewrites each talent's positions 0..n in the order the app showed until now
 * (primary first, then position) and sets is_primary on position 0 only, so
 * nobody's profile changes order. From here SkillsService keeps it derived.
 */
return new class extends Migration
{
    /**
     * Renumber every talent's skills and derive is_primary from the order.
     */
    public function up(): void
    {
        DB::table('talent_talent_type')
            ->orderBy('talent_id')
            ->orderByDesc('is_primary')
            ->orderBy('position')
            ->orderBy('talent_type_id')
            ->get(['talent_id', 'talent_type_id'])
            ->groupBy('talent_id')
            ->each(function ($rows, $talentId): void {
                foreach ($rows->values() as $index => $row) {
                    DB::table('talent_talent_type')
                        ->where('talent_id', $talentId)
                        ->where('talent_type_id', $row->talent_type_id)
                        ->update(['position' => $index, 'is_primary' => $index === 0]);
                }
            });
    }

    /**
     * Nothing to undo: the visible order is unchanged and the flag is still set.
     */
    public function down(): void {}
};
