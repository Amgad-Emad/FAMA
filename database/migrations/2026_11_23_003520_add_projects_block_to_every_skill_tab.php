<?php

use App\Actions\Catalog\SyncSkillCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every skill tab gets a Projects section (2026-11-23).
 *
 * The four oldest skills (Photography, Modelling, Styling, Makeup) never seeded a
 * `projects` block, so a project filed under one of those skills had nowhere to
 * render — a profile only shows projects where a `projects` block exists in that
 * scope (ADR-Q). The catalog now lists `projects` for every skill; this
 * migration (1) re-syncs the catalog's default_blocks and (2) backfills one
 * Projects block, appended LAST, into every (talent, held skill) scope that has
 * none — appended so nobody's chosen section order moves. Idempotent.
 */
return new class extends Migration
{
    /**
     * Sync the catalog, then backfill the missing Projects blocks.
     */
    public function up(): void
    {
        // Fresh database: the seeders build the catalog with the same data.
        if (! DB::table('talent_types')->where('slug', 'photography')->exists()) {
            return;
        }

        app(SyncSkillCatalog::class)->skills();

        $blockType = DB::table('block_types')->where('key', 'projects')->first();
        if ($blockType === null) {
            return;
        }

        DB::transaction(function () use ($blockType): void {
            $missing = DB::table('talent_talent_type as tt')
                ->whereNotExists(fn ($q) => $q->from('profile_blocks as pb')
                    ->whereColumn('pb.talent_id', 'tt.talent_id')
                    ->whereColumn('pb.talent_type_id', 'tt.talent_type_id')
                    ->where('pb.block_type_id', $blockType->id))
                ->get(['tt.talent_id', 'tt.talent_type_id']);

            $now = now();
            foreach ($missing as $row) {
                $position = (int) DB::table('profile_blocks')
                    ->where('talent_id', $row->talent_id)
                    ->where('talent_type_id', $row->talent_type_id)
                    ->max('position');

                DB::table('profile_blocks')->insert([
                    'talent_id' => $row->talent_id,
                    'block_type_id' => $blockType->id,
                    'talent_type_id' => $row->talent_type_id,
                    'title' => $blockType->name,
                    'position' => $position + 1,
                    'is_visible' => true,
                    'status' => 'visible',
                    'layout' => $blockType->default_layout ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    /**
     * Not reversible: a backfilled block is indistinguishable from one the talent
     * kept on purpose, and removing it would hide their projects again.
     */
    public function down(): void
    {
        //
    }
};
