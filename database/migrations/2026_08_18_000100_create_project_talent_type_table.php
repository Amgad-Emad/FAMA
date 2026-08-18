<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Projects carry ONE OR MORE skills (ADR-X). ADR-Q gave `projects` a single
 * `talent_type_id` scope — which tab the project renders in — but a real piece
 * of work is rarely one discipline (a campaign film is cinematography AND
 * creative direction), and the talent-facing Projects page requires at least one
 * skill per project.
 *
 * This adds the `project_talent_type` pivot and keeps `projects.talent_type_id`
 * as the PRIMARY skill (the first tag) so every existing reader — the public
 * profile's tab scoping, PublicProfileResource, the un-scoping in
 * SkillsService::removeType — keeps working unchanged. The pivot is the source
 * of truth for "does this project belong in that tab"; the column is the
 * project's home tab.
 *
 * Backfill: each already-scoped project gets its existing skill as tag 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_talent_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('talent_type_id')->constrained('talent_types')->cascadeOnDelete();
            // Order of the tags on the project (0 = the primary skill).
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'talent_type_id']);
            $table->index('talent_type_id');
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('project_talent_type');
    }

    /**
     * Tag every already-scoped project with its ADR-Q skill. Returns the count
     * (public so the backfill is unit-testable against legacy rows).
     */
    public function backfill(): int
    {
        $tagged = 0;
        $now = now();

        DB::table('projects')->whereNotNull('talent_type_id')->orderBy('id')
            ->chunkById(500, function ($rows) use (&$tagged, $now): void {
                $inserted = DB::table('project_talent_type')->insertOrIgnore(
                    collect($rows)->map(fn ($row) => [
                        'project_id' => $row->id,
                        'talent_type_id' => $row->talent_type_id,
                        'position' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );

                $tagged += $inserted;
            });

        return $tagged;
    }
};
