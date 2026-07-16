<?php

use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

/**
 * The `2026_07_11_000200` backfill (ADR-Q). The columns already exist (RefreshDatabase
 * ran the migration), so we create legacy-shaped rows (talent_type_id = NULL) and
 * invoke the migration's backfill methods directly.
 */
beforeEach(fn () => $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]));

function runBackfill(): void
{
    $migration = require database_path('migrations/2026_07_11_000200_add_skill_scope_to_blocks_and_projects.php');
    $migration->backfillProjects();
    $migration->backfillProfileBlocks();
}

function legacyBlock(Talent $talent, string $key): int
{
    $bt = BlockType::where('key', $key)->firstOrFail();

    return $talent->profileBlocks()->create([
        'block_type_id' => $bt->id, 'talent_type_id' => null, 'title' => ['en' => 'x'],
        'position' => 0, 'is_visible' => true, 'status' => 'visible', 'settings' => [],
    ])->id;
}

it('stamps a gated block that matches exactly one skill, leaves universal blocks NULL', function () {
    $talent = Talent::factory()->create();
    $model = TalentType::where('slug', 'modeling')->firstOrFail();          // category model
    $photographer = TalentType::where('slug', 'photography')->firstOrFail(); // category crew
    $talent->talentTypes()->attach([
        $model->id => ['is_primary' => true, 'position' => 0],
        $photographer->id => ['is_primary' => false, 'position' => 1],
    ]);

    $comp = legacyBlock($talent, 'comp_card'); // by_category [model] → only the model skill
    $gallery = legacyBlock($talent, 'gallery'); // universal → stays NULL
    $project = Project::factory()->for($talent)->create(['talent_type_id' => null])->id;

    runBackfill();

    expect(ProfileBlock::find($comp)->talent_type_id)->toBe($model->id);
    expect(ProfileBlock::find($gallery)->talent_type_id)->toBeNull();
    expect(Project::find($project)->talent_type_id)->toBe($model->id); // primary skill
});

it('leaves an AMBIGUOUS gated block NULL (its gate matches more than one skill)', function () {
    $talent = Talent::factory()->create();
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();   // crew
    $cinematographer = TalentType::where('slug', 'cinematography')->firstOrFail(); // crew
    $talent->talentTypes()->attach([
        $photographer->id => ['is_primary' => true, 'position' => 0],
        $cinematographer->id => ['is_primary' => false, 'position' => 1],
    ]);

    $showreel = legacyBlock($talent, 'showreel'); // by_category [crew, creative] → both crew skills match

    runBackfill();

    expect(ProfileBlock::find($showreel)->talent_type_id)->toBeNull();
});
