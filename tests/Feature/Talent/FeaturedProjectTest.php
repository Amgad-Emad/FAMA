<?php

use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

/**
 * The featured project (the public profile's cover) is the talent's pick: a
 * talent with projects always has exactly one featured, an only project is
 * featured and locked, and the pick changes by featuring another.
 */
beforeEach(function () {
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
    $this->talent = Talent::factory()->create();
    $this->skill = TalentType::where('slug', 'modeling')->firstOrFail();
    $this->talent->talentTypes()->attach($this->skill->id, ['is_primary' => true, 'position' => 0]);
});

/** Create a project through the real endpoint; returns its JSON data. */
function newFeaturedTestProject(string $title, array $extra = []): array
{
    return test()->actingAs(test()->talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => $title],
        'skill_ids' => [test()->skill->id],
    ] + $extra)->assertCreated()->json('data');
}

it('features a first project automatically and locks it', function () {
    $data = newFeaturedTestProject('Only one');

    expect($data['is_featured'])->toBeTrue()
        ->and($data['featured_locked'])->toBeTrue()
        ->and($this->talent->fresh()->featured_project_id)->toBe($data['id']);
});

it('keeps the current pick when another project is added', function () {
    $first = newFeaturedTestProject('First');
    $second = newFeaturedTestProject('Second');

    expect($second['is_featured'])->toBeFalse()
        ->and($this->talent->fresh()->featured_project_id)->toBe($first['id']);
});

it('features a new project when asked to on publish', function () {
    newFeaturedTestProject('First');
    $second = newFeaturedTestProject('Second', ['featured' => true]);

    expect($second['is_featured'])->toBeTrue()
        ->and($this->talent->fresh()->featured_project_id)->toBe($second['id']);
});

it('switches the featured project from the list', function () {
    $first = newFeaturedTestProject('First');
    $second = newFeaturedTestProject('Second');

    $this->postJson(route('talent.projects.feature', $second['id']))->assertOk();

    $rows = collect($this->getJson(route('talent.projects.data'))->assertOk()->json('data'))->keyBy('id');
    expect($rows[$second['id']]['is_featured'])->toBeTrue()
        ->and($rows[$first['id']]['is_featured'])->toBeFalse()
        ->and($rows[$second['id']]['featured_locked'])->toBeFalse();
});

it('refuses to feature someone else\'s project', function () {
    $other = Project::factory()->create();

    $this->actingAs($this->talent, 'talent')
        ->postJson(route('talent.projects.feature', $other->id))
        ->assertStatus(422);

    expect($this->talent->fresh()->featured_project_id)->toBeNull();
});

it('hands the cover to the next project when the featured one is deleted', function () {
    $first = newFeaturedTestProject('First');
    $second = newFeaturedTestProject('Second');

    $this->deleteJson(route('talent.projects.destroy', $first['id']))->assertOk();

    expect($this->talent->fresh()->featured_project_id)->toBe($second['id']);
});

it('shows the picked project on the public profile cover', function () {
    newFeaturedTestProject('Cover A');
    $b = newFeaturedTestProject('Cover B', ['featured' => true]);
    $this->talent->update(['is_published' => true, 'is_verified' => true]);

    $html = $this->get('/'.$this->talent->slug)->assertOk()->getContent();

    expect($html)->toContain(route('talent.work', ['slug' => $this->talent->slug, 'project' => $b['id']]));
    preg_match('/class="ct-cover[^"]*" href="([^"]+)"/', $html, $m);
    expect($m[1] ?? '')->toContain('/work/'.$b['id']);
});
