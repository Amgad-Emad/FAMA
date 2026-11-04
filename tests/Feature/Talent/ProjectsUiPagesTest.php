<?php

use App\Models\Project;
use App\Models\Recognition;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\ProjectCreditService;
use App\Services\SkillsService;
use App\Services\TalentProjectService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\SpecialitySeeder;
use Database\Seeders\TalentTypeSeeder;

/**
 * The fama-ui pages of the projects area (2026-10-12 redesign): the project
 * page, Credits & recognitions, and Recognise — rendered with real data.
 */
beforeEach(fn () => $this->seed([TalentTypeSeeder::class, SpecialitySeeder::class, BlockTypeSeeder::class]));

/** A published talent holding the given skill (seeds its blocks, incl. profile defaults). */
function uiTalent(string $name, string $skill = 'photography'): Talent
{
    $talent = Talent::factory()->create(['display_name' => $name, 'is_published' => true, 'is_verified' => true]);
    app(SkillsService::class)->addType($talent, TalentType::where('slug', $skill)->firstOrFail());

    return $talent;
}

/** A project owned by the talent, tagged with their first skill. */
function uiProject(Talent $owner, array $attributes = []): Project
{
    return app(TalentProjectService::class)->create(
        $owner,
        $attributes + ['title' => ['en' => 'Desert Light']],
        [$owner->talentTypes()->firstOrFail()->id],
    );
}

it('renders the project page as a story with results, public credits and more work', function () {
    $owner = uiTalent('Lina Haddad');
    $peer = uiTalent('Omar Saleh', 'styling');
    $project = uiProject($owner, ['client_name' => 'Maison Nour', 'results' => [['label' => 'Reach', 'value' => '1.2M'], ['label' => 'Sell-through', 'value' => '68%'], ['label' => 'Visits', 'value' => '+40%']]]);
    uiProject($owner, ['title' => ['en' => 'Studio portraits']]);

    app(ProjectCreditService::class)->add($project, ['name' => 'Hana Mostafa', 'talent_type_id' => $peer->talentTypes()->first()->id, 'role' => 'Director']);
    app(ProjectCreditService::class)->add($project, ['talent_id' => $peer->id, 'talent_type_id' => $peer->talentTypes()->first()->id, 'role' => 'Key stylist']);

    $this->get(route('talent.work', ['slug' => $owner->slug, 'project' => $project->id]))
        ->assertOk()
        ->assertSee('class="ed is-short"', false)   // the editorial case study (project-page.css)
        ->assertSee('fama-ui/css/project-page.css', false)
        ->assertSee('Maison Nour')
        ->assertSee('data-kind="percent"', false)
        ->assertSee('data-kind="change"', false)
        ->assertSee('THE TEAM')
        ->assertSee('famaProjectPage(', false)
        ->assertSee('Hana Mostafa')          // off-platform: listed at once
        ->assertDontSee('Key stylist')       // pending: appears nowhere
        ->assertSee('Studio portraits')      // more from this talent
        ->assertSee('famaProjectFollow(', false)
        ->assertDontSee('Edit project');
});

it('splits "Name — 2026" into the headline and the year tag', function () {
    $owner = uiTalent('Lina Haddad');
    $project = uiProject($owner, ['title' => ['en' => 'Desert Light — 2026']]);

    $this->get(route('talent.work', ['slug' => $owner->slug, 'project' => $project->id]))
        ->assertOk()
        ->assertSee('<span class="ytag">2026</span>', false)
        ->assertSee('<span class="vh">, 2026</span>', false)
        ->assertDontSee('h1-sub', false);
});

it('renders the editor as the project page, with the docked bar and Settings', function () {
    $owner = uiTalent('Lina Haddad');
    $project = uiProject($owner, ['client_name' => 'Maison Nour']);

    $this->actingAs($owner, 'talent')->get(route('talent.projects.edit', $project))
        ->assertOk()
        ->assertSee('fama-ui/css/project-page.css', false)
        ->assertSee('fama-ui/css/project-editor.css', false)
        ->assertSee('famaProjectEdit(', false)
        ->assertSee('class="pe-head"', false)
        ->assertSee('x-teleport=".fx-nav > .fx-in"', false)
        ->assertSee('cover pe-cover', false)
        ->assertSee('colo pe-colo', false)
        ->assertSee('class="pe-panel"', false);

    $this->actingAs($owner, 'talent')->get(route('talent.projects.new'))
        ->assertOk()
        ->assertSee('famaProjectEdit(', false)
        ->assertSee('Publish');
});

it('shows the owner an Edit button and the preview strip', function () {
    $owner = uiTalent('Lina Haddad');
    $project = uiProject($owner);

    $this->actingAs($owner, 'talent')
        ->get(route('talent.work', ['slug' => $owner->slug, 'project' => $project->id, 'preview' => 1]))
        ->assertOk()
        ->assertSee('Edit project')
        ->assertSee('Back to editing')
        ->assertSee(route('talent.projects.edit', $project), false);
});

it('renders Credits & recognitions with the received recognitions', function () {
    $talent = uiTalent('Demo Talent');
    $author = uiTalent('Omar Saleh');

    Recognition::factory()->approved()->create([
        'talent_id' => $talent->id, 'author_talent_id' => $author->id,
        'relationship' => 'Shot three campaigns together',
        'body' => 'He finds the story in a rail of clothes before anyone else does.',
    ]);
    Recognition::factory()->rejected()->create(['talent_id' => $talent->id, 'body' => 'Rejected words never show here.']);

    $this->actingAs($talent, 'talent')->get(route('talent.credits'))
        ->assertOk()
        ->assertSee('famaCredits(', false)
        ->assertSee('Credits &amp; recognitions', false)
        ->assertSee('He finds the story in a rail of clothes')
        ->assertSee('Shot three campaigns together')
        ->assertDontSee('Rejected words never show here.')
        // The section switch drives the talent's own recognitions block.
        ->assertSee('famaRecognitionsSwitch(', false)
        ->assertSee('role="switch"', false);
});

it('renders the recognise form, pre-filled when the peer already wrote one', function () {
    $subject = uiTalent('Demo Talent');
    $peer = uiTalent('Omar Saleh');

    $this->actingAs($peer, 'talent')->get(route('talent.recognition.create', ['slug' => $subject->slug]))
        ->assertOk()
        ->assertSee('Recognise Demo Talent')
        ->assertSee('famaRecognise(', false)
        ->assertSee('Send recognition');

    Recognition::factory()->create(['talent_id' => $subject->id, 'author_talent_id' => $peer->id, 'relationship' => 'Assisted me for two years']);

    $this->actingAs($peer, 'talent')->get(route('talent.recognition.create', ['slug' => $subject->slug]))
        ->assertOk()
        ->assertSee('Assisted me for two years')
        ->assertSee('Send updated recognition');
});
