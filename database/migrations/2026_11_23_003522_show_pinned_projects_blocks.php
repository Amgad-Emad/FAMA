<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A skill tab's Projects section can no longer be hidden (2026-11-23,
 * ProfileBlockService::isPinned) — switch any that were hidden back on so the
 * editor's state matches the public profile, which always shows the work.
 */
return new class extends Migration
{
    /**
     * Unhide every skill-scoped Projects block.
     */
    public function up(): void
    {
        $projects = DB::table('block_types')->where('key', 'projects')->value('id');
        if ($projects === null) {
            return;
        }

        DB::table('profile_blocks')
            ->where('block_type_id', $projects)
            ->whereNotNull('talent_type_id')
            ->update(['is_visible' => true, 'status' => 'visible', 'updated_at' => now()]);
    }

    /**
     * Not reversible: which ones were hidden before is not kept.
     */
    public function down(): void
    {
        //
    }
};
