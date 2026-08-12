<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retire the `hero` block (ADR-O follow-up). The cover/hero region was replaced
 * by the avatar-led header the profile renders itself, so every public surface
 * already skipped the block — it survived only as an editable row in the talent
 * editor that changed nothing. This drops the placements, strips the key from
 * the skills' `default_blocks`, and deactivates the catalog entry so it is
 * neither re-seeded nor offered by the picker. The `block_types` row stays (keys
 * are frozen; deactivated types are grandfathered by design).
 */
return new class extends Migration
{
    public function up(): void
    {
        $heroId = DB::table('block_types')->where('key', 'hero')->value('id');

        if ($heroId === null) {
            return;
        }

        DB::table('profile_blocks')->where('block_type_id', $heroId)->delete();
        DB::table('block_types')->where('id', $heroId)->update(['is_active' => false]);

        // Strip 'hero' from each skill's ordered default_blocks list (JSON column).
        foreach (DB::table('talent_types')->select('id', 'default_blocks')->get() as $type) {
            $keys = json_decode((string) $type->default_blocks, true);

            if (! is_array($keys) || ! in_array('hero', $keys, true)) {
                continue;
            }

            DB::table('talent_types')->where('id', $type->id)->update([
                'default_blocks' => json_encode(array_values(array_diff($keys, ['hero']))),
            ]);
        }
    }

    /**
     * Reactivate the catalog entry only — deleted placements are not restored
     * (they carried no content: the hero block was inline-sourced and empty).
     */
    public function down(): void
    {
        DB::table('block_types')->where('key', 'hero')->update(['is_active' => true]);
    }
};
