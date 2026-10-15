<?php

use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\SkillsService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Project results (fama-ui handoff): an ordered list of up to four
 * {label, value, compare}; the value decides the display — a number, a percent
 * bar, or a rise/fall with its "Compared with" line.
 */
beforeEach(function () {
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
    $this->talent = Talent::factory()->create(['is_published' => true, 'is_verified' => true]);
    app(SkillsService::class)->addType($this->talent, TalentType::where('slug', 'photography')->firstOrFail());
    $this->skill = $this->talent->talentTypes()->first()->id;
});

it('stores results as ordered rows with a compare line', function () {
    $id = $this->actingAs($this->talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'Desert Light'],
        'skill_ids' => [$this->skill],
        'results' => [
            ['label' => 'Store visits', 'value' => '+40%', 'compare' => 'vs last season'],
            ['label' => 'Reach', 'value' => '1.2M', 'compare' => ''],
            ['label' => '', 'value' => ''],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.results.0.compare', 'vs last season')
        ->json('data.id');

    expect(Project::find($id)->results)->toBe([
        ['label' => 'Store visits', 'value' => '+40%', 'compare' => 'vs last season'],
        ['label' => 'Reach', 'value' => '1.2M', 'compare' => ''],
    ]);
});

it('caps a project at four results', function () {
    $rows = array_fill(0, 5, ['label' => 'Reach', 'value' => '1M']);

    $this->actingAs($this->talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'Too many'], 'skill_ids' => [$this->skill], 'results' => $rows,
    ])->assertStatus(422)->assertJsonValidationErrors('results');
});

it('renders number, percent bar and rise/fall with the compare line on the project page', function () {
    $project = Project::factory()->for($this->talent)->create([
        'talent_type_id' => $this->skill,
        'results' => [
            ['label' => 'Reach', 'value' => '1.2M', 'compare' => ''],
            ['label' => 'Sell-through', 'value' => '68%', 'compare' => ''],
            ['label' => 'Store visits', 'value' => '+40%', 'compare' => 'vs last season'],
            ['label' => 'Returns', 'value' => '-12%', 'compare' => 'vs SS25'],
        ],
    ]);

    $this->get(route('talent.work', ['slug' => $this->talent->slug, 'project' => $project->id]))
        ->assertOk()
        ->assertSee('r r-number', false)
        ->assertSee('r r-percent', false)
        ->assertSee('style="width:68%"', false)
        ->assertSee('r r-change"', false)
        ->assertSee('r r-change down', false)
        ->assertSee('<small>vs last season</small>', false)
        ->assertSee('<small>vs SS25</small>', false);
});

it('still reads a legacy label→value map, and the migration converts it', function () {
    $project = Project::factory()->for($this->talent)->create(['talent_type_id' => $this->skill, 'results' => null]);
    DB::table('projects')->where('id', $project->id)->update(['results' => json_encode(['Reach' => '2.4M', 'Engagement' => '+38%'])]);

    expect($project->fresh()->resultRows())->toBe([
        ['label' => 'Reach', 'value' => '2.4M', 'compare' => ''],
        ['label' => 'Engagement', 'value' => '+38%', 'compare' => ''],
    ]);

    (require database_path('migrations/2026_10_12_000300_convert_project_results_to_rows.php'))->up();

    expect($project->fresh()->results)->toBe([
        ['label' => 'Reach', 'value' => '2.4M', 'compare' => ''],
        ['label' => 'Engagement', 'value' => '+38%', 'compare' => ''],
    ]);
});
