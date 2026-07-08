<?php

use App\Models\Project;
use App\Models\Talent;

beforeEach(fn () => $this->withoutVite());

it('renders a published talent’s project', function () {
    $talent = Talent::factory()->create(['slug' => 'jane']);
    $project = Project::factory()->for($talent)->create(['title' => ['en' => 'Big Campaign']]);

    $this->get(route('talent.work', ['slug' => 'jane', 'project' => $project->id]))
        ->assertOk()
        ->assertSee('Big Campaign');
});

it('404s for a project that does not belong to the talent', function () {
    Talent::factory()->create(['slug' => 'a']);
    $other = Talent::factory()->create(['slug' => 'b']);
    $project = Project::factory()->for($other)->create();

    $this->get(route('talent.work', ['slug' => 'a', 'project' => $project->id]))->assertNotFound();
});

it('404s for a project on an unpublished talent', function () {
    $talent = Talent::factory()->draft()->create(['slug' => 'hidden']);
    $project = Project::factory()->for($talent)->create();

    $this->get(route('talent.work', ['slug' => 'hidden', 'project' => $project->id]))->assertNotFound();
});
