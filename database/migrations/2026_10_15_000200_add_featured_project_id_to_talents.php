<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The featured project (the public profile's cover) becomes the talent's own
 * choice (2026-10-15). A talent with projects always has exactly one featured
 * — FeaturedProjectService keeps it valid on create / delete / leave. Backfill
 * = the project the cover already showed: the first (by position) with a
 * cover or gallery picture, else the first.
 */
return new class extends Migration
{
    /** Add the column and point it at today's cover project. */
    public function up(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->foreignId('featured_project_id')->nullable()->after('meta')
                ->constrained('projects')->nullOnDelete();
        });

        $withPicture = DB::table('media')->where('model_type', 'project')
            ->whereIn('collection_name', ['cover', 'media'])->distinct()->pluck('model_id')->flip();

        DB::table('projects')->orderBy('talent_id')->orderBy('position')->orderBy('id')
            ->get(['id', 'talent_id'])->groupBy('talent_id')
            ->each(function ($projects, $talentId) use ($withPicture): void {
                $pick = $projects->first(fn ($p) => $withPicture->has($p->id)) ?? $projects->first();
                DB::table('talents')->where('id', $talentId)->update(['featured_project_id' => $pick->id]);
            });
    }

    /** Drop the column. */
    public function down(): void
    {
        Schema::table('talents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('featured_project_id');
        });
    }
};
