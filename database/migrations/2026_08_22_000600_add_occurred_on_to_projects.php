<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The project spec's DATE — "recorded, not displayed" (ADR-AA).
 *
 * Stored so work can be ordered by recency and so "shot recently" is an
 * answerable question. It deliberately does NOT appear on the page: a strong
 * project from three years ago should not be discounted for its age, and a date
 * in the corner of a card is exactly how that discounting happens.
 *
 * `year` is KEPT and kept in sync (see Project::occurred_on's mutator) rather
 * than dropped: the feed's project spreads snapshot it, and existing rows,
 * resources and tests read it. `occurred_on` is the field the editor writes from
 * here on; `year` becomes a derived projection of it.
 *
 * The backfill maps a bare year to 1 July — mid-year, so ordering by
 * `occurred_on` puts a legacy row sensibly among dated ones rather than
 * clustering every old project on 1 January.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->date('occurred_on')->nullable()->after('year');

            // Recency ordering + "shot in the last N months" both read this.
            $table->index(['talent_id', 'occurred_on']);
        });

        DB::table('projects')
            ->whereNotNull('year')
            ->whereNull('occurred_on')
            ->update(['occurred_on' => DB::raw("MAKEDATE(year, 182)")]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropIndex(['talent_id', 'occurred_on']);
            $table->dropColumn('occurred_on');
        });
    }
};
