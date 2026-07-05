<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the spatie/laravel-model-states `status` columns for the talent-side
 * lifecycles (talent profile, block, review, service, affiliation, portfolio
 * media). Availability reuses the existing `availability_status` enum.
 *
 * The existing booleans (is_published/is_visible/is_approved/is_active/
 * is_current) are kept as denormalised projections synced by the state machine
 * (see App\Listeners\SyncStateProjections). Existing rows are backfilled so the
 * two representations start consistent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->string('status')->default('draft')->index()->after('is_published');
        });
        DB::table('talents')->update(['status' => DB::raw("CASE WHEN is_published = 1 THEN 'live' ELSE 'draft' END")]);

        Schema::table('profile_blocks', function (Blueprint $table) {
            $table->string('status')->default('visible')->after('is_visible');
        });
        DB::table('profile_blocks')->update(['status' => DB::raw("CASE WHEN is_visible = 1 THEN 'visible' ELSE 'hidden' END")]);

        Schema::table('reviews', function (Blueprint $table) {
            $table->string('status')->default('pending')->index()->after('is_approved');
        });
        DB::table('reviews')->update(['status' => DB::raw("CASE WHEN is_approved = 1 THEN 'approved' ELSE 'pending' END")]);

        Schema::table('services', function (Blueprint $table) {
            $table->string('status')->default('active')->after('is_active');
        });
        DB::table('services')->update(['status' => DB::raw("CASE WHEN is_active = 1 THEN 'active' ELSE 'paused' END")]);

        Schema::table('agency_affiliations', function (Blueprint $table) {
            $table->string('status')->default('current')->after('is_current');
        });
        DB::table('agency_affiliations')->update(['status' => DB::raw("CASE WHEN is_current = 1 THEN 'current' ELSE 'past' END")]);

        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->string('status')->default('visible')->after('media_type');
        });
    }

    public function down(): void
    {
        Schema::table('talents', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('profile_blocks', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('reviews', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('agency_affiliations', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('portfolio_items', fn (Blueprint $table) => $table->dropColumn('status'));
    }
};
