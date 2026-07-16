<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Strips the removed features' profile blocks from the catalog and any existing
 * profiles: the `services`, `agency_affiliations` and `press_features` block
 * types, their category/type gates, and every profile_blocks row that used them.
 *
 * On a fresh migrate this is a no-op (block_types are seeded afterwards, and the
 * seeder no longer creates these keys); it cleans up already-migrated databases.
 */
return new class extends Migration
{
    private const KEYS = ['services', 'agency_affiliations', 'press_features'];

    public function up(): void
    {
        if (! Schema::hasTable('block_types')) {
            return;
        }

        $ids = DB::table('block_types')->whereIn('key', self::KEYS)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('profile_blocks')->whereIn('block_type_id', $ids)->delete();
        DB::table('block_type_category')->whereIn('block_type_id', $ids)->delete();

        if (Schema::hasTable('block_type_talent_type')) {
            DB::table('block_type_talent_type')->whereIn('block_type_id', $ids)->delete();
        }

        DB::table('block_types')->whereIn('id', $ids)->delete();
    }

    public function down(): void
    {
        // Data cleanup — not reversible (re-run BlockTypeSeeder to reseed the catalog).
    }
};
