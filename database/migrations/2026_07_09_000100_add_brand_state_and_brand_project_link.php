<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2B state wiring. Adds the brand lifecycle state column (the flags
 * `is_complete`/`is_published`/`is_active` become synced projections, same
 * convention as the talent side); `is_verified` stays an orthogonal one-way flag.
 * Also adds `contracts.brand_project_id` (ADR-F resolved) so contracts run under a project.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('status')->default('registered')->after('is_active');
            $table->index('status');
        });

        // Seed status from the existing flags for any already-migrated rows.
        DB::table('brands')->update(['status' => DB::raw(
            "CASE WHEN is_active = 0 THEN 'suspended'
                  WHEN is_published = 1 THEN 'published'
                  WHEN is_complete = 1 THEN 'complete'
                  ELSE 'registered' END"
        )]);

        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('brand_project_id')->nullable()->after('contract_flow_id')->constrained('brand_projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['brand_project_id']);
            $table->dropColumn('brand_project_id');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
