<?php

use App\Models\Project;
use App\Support\ProjectBlocks;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Free-form project blocks (ADR-AN, the prototype's WYSIWYG project editor).
 *
 * `projects.blocks` holds the story as an ordered list (App\Support\ProjectBlocks).
 * Every existing project is converted in place: its opening line, its photos in
 * their chosen order, its story's paragraphs, then Results and Credits — so the
 * page reads the same the day this ships. `summary` / `body` stay as derived
 * projections. `client_confidential` is the prototype's "Keep the client
 * confidential" setting (work under an NDA): the page says "Confidential client".
 */
return new class extends Migration
{
    /**
     * Add the columns and convert every project.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->json('blocks')->nullable()->after('body');
            $table->boolean('client_confidential')->default(false)->after('client_name');
        });

        Project::query()->with(['media', 'credits'])->chunkById(100, function ($projects): void {
            foreach ($projects as $project) {
                // A quiet write: no model events, no timestamps moved.
                Project::query()->whereKey($project->id)->update([
                    'blocks' => json_encode(ProjectBlocks::fromLegacy($project), JSON_UNESCAPED_UNICODE),
                ]);
            }
        });
    }

    /**
     * Drop the columns (summary / body still hold the text).
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn(['blocks', 'client_confidential']);
        });
    }
};
