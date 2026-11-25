<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Willing to explore: the note becomes a text area with the editor's normal
 * long-text limit (2,000 — like Point of view and Experience highlights)
 * instead of a 160-character one-liner, so the column widens from
 * VARCHAR(255) to TEXT. Existing notes are kept as they are.
 */
return new class extends Migration
{
    /** Widen talent_interests.note to TEXT. */
    public function up(): void
    {
        Schema::table('talent_interests', function (Blueprint $table) {
            $table->text('note')->nullable()->change();
        });
    }

    /** Back to VARCHAR(255); longer notes are cut to fit first so the change can't fail. */
    public function down(): void
    {
        DB::table('talent_interests')->whereRaw('CHAR_LENGTH(note) > 255')
            ->update(['note' => DB::raw('LEFT(note, 255)')]);

        Schema::table('talent_interests', function (Blueprint $table) {
            $table->string('note')->nullable()->change();
        });
    }
};
