<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin verification is now a hard prerequisite for going public. Brands already
 * carried `is_verified`; talents gain the same column. Existing public rows are
 * grandfathered (a currently-live talent / published brand is treated as already
 * verified) so the new publish guards never retroactively hide live profiles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talents', function (Blueprint $table): void {
            $table->boolean('is_verified')->default(false)->after('is_active');
        });

        // Grandfather already-public accounts so the gate only bites future
        // publish transitions, not profiles that are live today.
        DB::table('talents')->where('status', 'live')->update(['is_verified' => true]);
        DB::table('brands')->where('status', 'published')->update(['is_verified' => true]);
    }

    public function down(): void
    {
        Schema::table('talents', function (Blueprint $table): void {
            $table->dropColumn('is_verified');
        });
    }
};
