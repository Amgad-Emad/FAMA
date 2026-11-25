<?php

use App\Models\BlockType;
use App\Models\BrandCollab;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use App\Support\ProjectTitle;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

/*
 * The public profile on the latest fama-ui preview.html (Oct 2026): the hero that
 * answers a brand's questions in order beside the featured project, the
 * profile-wide "About {name}" region after the tabs, the not-live page, and the
 * GSAP motion layer.
 */

beforeEach(function () {
    $this->withoutVite();
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
});

it('shows availability as the yellow signal, the skill chips and the Worked with line', function () {
    $talent = Talent::factory()->create(['slug' => 'hero-one', 'is_published' => true, 'is_available' => true]);
    $talent->talentTypes()->attach(TalentType::where('slug', 'styling')->value('id'), ['is_primary' => true, 'position' => 0]);
    foreach (['Maison Nour', 'Nomad Coffee', 'Nefertari', 'Cairo Linen', 'Maison Nour'] as $i => $brand) {
        BrandCollab::create(['talent_id' => $talent->id, 'brand_name' => $brand, 'position' => $i]);
    }

    $this->get('/hero-one')->assertOk()
        ->assertSee('<span class="on"', false)
        ->assertSee(__('Open for work'))
        ->assertSee('class="ct-skills"', false)
        ->assertSee('data-goskill="styling"', false)
        ->assertSee(__('Worked with'))
        ->assertSee('Maison Nour, Nomad Coffee, Nefertari')
        ->assertSee(__('and :n more', ['n' => 1]))           // four distinct brands, three named
        ->assertDontSee('<dt>'.__('Brands').'</dt>', false);  // no stats row (removed 2026-11-23)
});

it('puts the featured project on the cover with its name and client', function () {
    $talent = Talent::factory()->create(['slug' => 'hero-two', 'is_published' => true]);
    $project = Project::factory()->for($talent)->create(['title' => ['en' => 'Desert Light — 2026'], 'client_name' => 'Maison Nour']);
    $talent->forceFill(['featured_project_id' => $project->id])->save();

    $this->get('/hero-two')->assertOk()
        ->assertSee('class="ct-coverwrap"', false)
        ->assertSee(__('Featured project'))
        ->assertSee('<b>Desert Light</b>', false)            // the year leaves the name…
        ->assertSee('<span class="ptag">2026</span>', false) // …and becomes the tag
        ->assertSee('<small>Maison Nour</small>', false)
        ->assertSee(route('talent.work', ['slug' => 'hero-two', 'project' => $project->id]), false);
});

it('falls back to the recorded year for the tag when the title has none (prototype, supersedes ADR-AA)', function () {
    $talent = Talent::factory()->create(['slug' => 'hero-three', 'is_published' => true]);
    Project::factory()->for($talent)->create(['title' => ['en' => 'Untitled shoot'], 'occurred_on' => '2021-05-01']);

    $this->get('/hero-three')->assertOk()->assertSee('<span class="ptag">2021</span>', false);
});

it('splits a trailing year off a project title, and only a year', function () {
    expect(ProjectTitle::split('Desert Light — 2026'))->toBe(['name' => 'Desert Light', 'sub' => '', 'year' => '2026'])
        ->and(ProjectTitle::split('Nomad Coffee — Autumn launch'))->toBe(['name' => 'Nomad Coffee', 'sub' => 'Autumn launch', 'year' => ''])
        ->and(ProjectTitle::split('Cairo Fashion Week 2024'))->toBe(['name' => 'Cairo Fashion Week 2024', 'sub' => '', 'year' => ''])
        ->and(ProjectTitle::split(null))->toBe(['name' => '', 'sub' => '', 'year' => '']);
});

it('shows the point of view once, in the hero, before the tabs (13 Nov 2026)', function () {
    $talent = Talent::factory()->create([
        'slug' => 'hero-four', 'display_name' => 'Hero Four', 'is_published' => true,
        'worldview' => ['en' => 'Light first, then the clothes.'],
    ]);
    $type = TalentType::where('slug', 'photography')->firstOrFail();
    $talent->talentTypes()->attach($type->id, ['is_primary' => true, 'position' => 0]);
    $talent->profileBlocks()->create([
        'block_type_id' => BlockType::where('key', 'gallery')->value('id'),
        'talent_type_id' => $type->id, 'position' => 0, 'is_visible' => true, 'status' => 'visible', 'settings' => [],
    ]);

    $html = $this->actingAs($talent, 'talent')->get('/hero-four')->assertOk()->getContent();

    expect($html)->toContain('class="ct-pov"')
        ->not->toContain('class="povq"')
        // once on the PAGE (the <head>'s description / share card / Person data repeat it on purpose — ADR-AO)
        ->and(substr_count(strstr($html, '<body'), 'Light first, then the clothes.'))->toBe(1)
        ->and(strpos($html, 'class="ct-pov"'))->toBeLessThan(strpos($html, 'x-ref="panel"'));
});

it('leaves the profile-wide region out when nothing in it has content for a visitor', function () {
    $talent = Talent::factory()->create(['slug' => 'hero-five', 'is_published' => true, 'worldview' => null]);
    $talent->profileBlocks()->create([
        'block_type_id' => BlockType::where('key', 'brand_collabs')->value('id'),
        'talent_type_id' => null, 'position' => 0, 'is_visible' => true, 'status' => 'visible', 'settings' => [],
    ]);

    $this->get('/hero-five')->assertOk()->assertDontSee('id="universal-blocks"', false);
});

it('gives visitors the not-live page for a private profile, as a 404 that names nobody', function () {
    Talent::factory()->create(['slug' => 'not-yet', 'display_name' => 'Secret Person', 'is_published' => false]);

    $this->get('/not-yet')->assertNotFound()
        ->assertSee(__('This profile is still being set up.'))
        ->assertSee(__('Browse the feed'))
        ->assertDontSee('Secret Person');

    $this->get('/nobody-at-all')->assertNotFound()->assertDontSee(__('This profile is still being set up.'));
});

it('loads GSAP 3.12.5 + ScrollTrigger and the profile motion layer', function () {
    $talent = Talent::factory()->create(['is_published' => true]);

    $this->get('/'.$talent->slug)->assertOk()
        ->assertSee('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js', false)
        ->assertSee('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js', false)
        ->assertSee('fama-ui/js/profile-motion.js', false);
});

it('marks the open skill tab with the FAMA star and its project count — none when it has no projects', function () {
    $talent = Talent::factory()->create(['slug' => 'hero-six', 'is_published' => true]);
    foreach (['modeling', 'photography'] as $i => $slug) {
        $id = TalentType::where('slug', $slug)->value('id');
        $talent->talentTypes()->attach($id, ['is_primary' => $i === 0, 'position' => $i]);
        $talent->profileBlocks()->create([
            'block_type_id' => BlockType::where('key', 'gallery')->value('id'),
            'talent_type_id' => $id, 'position' => 0, 'is_visible' => true, 'status' => 'visible', 'settings' => [],
        ]);
    }

    // No projects anywhere: no "0" badge (owner 2026-11-25).
    $this->get('/hero-six')->assertOk()
        ->assertSee('class="tstar"', false)
        ->assertSee('id="skill-tab-modeling"', false)
        ->assertDontSee('<span class="n">', false);

    // Two projects in modeling: its tab carries "2"; photography still shows no badge.
    \App\Models\Project::factory()->count(2)->for($talent)->create(['talent_type_id' => TalentType::where('slug', 'modeling')->value('id')]);
    $html = $this->get('/hero-six')->assertOk()->getContent();
    expect($html)->toContain('<span class="vh">, </span>2')
        ->and(substr_count($html, '<span class="n">'))->toBe(1);
});
