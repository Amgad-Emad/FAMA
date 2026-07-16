<?php

use App\Models\Equipment;
use App\Models\LookType;
use App\Models\SoftwareStack;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->withoutVite());

it('renders the skills-first discovery page with an advanced-filters entry', function () {
    $this->get(route('discover'))
        ->assertOk()
        ->assertSee(__('Skills'))
        ->assertSee(__('Advanced filters'));
});

it('renders the primary skills filter as THE main control — sticky, toggle chips, summary, count', function () {
    $this->seed(TalentTypeSeeder::class);

    $this->get(route('discover'))
        ->assertOk()
        ->assertSee('clearSkills()', false)                  // "All" clear/reset affordance
        ->assertSee('sticky top-16', false)                  // sticky under the site header
        ->assertSee(':aria-pressed="filters.type.includes', false) // chips are a11y toggle buttons
        ->assertSee('role="group"', false)                   // grouped by scope
        ->assertSee('activeSummary', false)                  // active-filter summary row
        ->assertSee('resultTotal', false);                   // live result count
});

it('teleports the advanced-filters dialog to <body> with modal a11y wired up', function () {
    $this->get(route('discover'))
        ->assertOk()
        ->assertSee('x-teleport="body"', false)              // escapes any transformed/overflow ancestor
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-modal="true"', false)
        ->assertSee('aria-labelledby="advfilters-title"', false)
        ->assertSee('trapFocus($event)', false)              // focus trap
        ->assertSee('var(--scrim)', false);                  // token-based backdrop scrim
});

it('shows the skill-specific scoped filters in the modal, revealed BASED ON the selected skills', function () {
    $this->get(route('discover'))
        ->assertOk()
        // The modal holds the staged Skills selector + Location…
        ->assertSee('draft.type.includes', false)            // staged skill chips
        ->assertSee(__('Location'))
        // …and the Skill-specific scoped group, each select bound to the draft and
        // shown by its scope (crew → Equipment, creative → Software, modeling → Looks).
        ->assertSee(__('Skill-specific'))
        ->assertSee(__('Equipment'))->assertSee(__('Software'))->assertSee(__('Looks'))
        ->assertSee('draft.equipment', false)
        ->assertSee('draft.software', false)
        ->assertSee('draft.looks', false)
        ->assertSee('showEquipment', false)                  // crew → Equipment
        ->assertSee('showSoftware', false)                   // creative → Software
        ->assertSee('showLooks', false);                     // modeling → Looks
});

it('lists only published talents, paginated', function () {
    Talent::factory()->count(3)->create();
    Talent::factory()->draft()->create();

    $this->getJson(route('discover.search'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.pagination.total', 3);
});

it('filters by skill type through the pivot (the primary filter)', function () {
    $this->seed(TalentTypeSeeder::class);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();

    $a = Talent::factory()->create();
    $a->talentTypes()->attach($model->id, ['is_primary' => true, 'position' => 0]);
    $b = Talent::factory()->create();
    $b->talentTypes()->attach($photographer->id, ['is_primary' => true, 'position' => 0]);

    $response = $this->getJson(route('discover.search', ['filter' => ['type' => 'modeling']]))->assertOk();

    expect($response->json('meta.pagination.total'))->toBe(1);
    expect($response->json('data.0.slug'))->toBe($a->slug);
});

it('multi-selects skill types (comma-separated → OR match across the pivot)', function () {
    $this->seed(TalentTypeSeeder::class);
    $modeling = TalentType::where('slug', 'modeling')->firstOrFail();
    $photography = TalentType::where('slug', 'photography')->firstOrFail();
    $styling = TalentType::where('slug', 'styling')->firstOrFail();

    $a = Talent::factory()->create();
    $a->talentTypes()->attach($modeling->id, ['is_primary' => true, 'position' => 0]);
    $b = Talent::factory()->create();
    $b->talentTypes()->attach($photography->id, ['is_primary' => true, 'position' => 0]);
    $c = Talent::factory()->create();
    $c->talentTypes()->attach($styling->id, ['is_primary' => true, 'position' => 0]);

    $res = $this->getJson(route('discover.search', ['filter' => ['type' => 'modeling,photography']]))->assertOk();

    expect($res->json('meta.pagination.total'))->toBe(2);
    $slugs = collect($res->json('data'))->pluck('slug');
    expect($slugs)->toContain($a->slug)->toContain($b->slug)->not->toContain($c->slug);
});

it('holds active filters across pagination (multi-select type + page 2)', function () {
    $this->seed(TalentTypeSeeder::class);
    $modeling = TalentType::where('slug', 'modeling')->firstOrFail();

    // 15 modeling talents (2 pages) + 3 of another skill that must never appear.
    $photography = TalentType::where('slug', 'photography')->firstOrFail();
    Talent::factory()->count(15)->create()->each(fn ($t) => $t->talentTypes()->attach($modeling->id, ['is_primary' => true, 'position' => 0]));
    Talent::factory()->count(3)->create()->each(fn ($t) => $t->talentTypes()->attach($photography->id, ['is_primary' => true, 'position' => 0]));

    $page2 = $this->getJson(route('discover.search', ['filter' => ['type' => 'modeling'], 'page' => 2]))->assertOk();

    expect($page2->json('meta.pagination.total'))->toBe(15);        // filter held on page 2
    expect($page2->json('meta.pagination.current_page'))->toBe(2);
    expect($page2->json('data'))->toHaveCount(3);
});

it('scopes filters by category: crew→equipment, creative→software, model→looks', function () {
    // Crew scope → Equipment.
    $crew = Talent::factory()->create();
    Equipment::factory()->for($crew)->create(['category' => 'camera']);
    // Creative scope → Software.
    $creative = Talent::factory()->create();
    SoftwareStack::factory()->for($creative)->create(['software_name' => 'Figma']);
    // Model scope → Looks (translatable name matched on the English path, indexed).
    $model = Talent::factory()->create();
    LookType::factory()->for($model)->create(['name' => ['en' => 'Editorial', 'ar' => 'إطلالة']]);

    expect($this->getJson(route('discover.search', ['filter' => ['equipment' => 'camera']]))->json('data.0.slug'))->toBe($crew->slug);
    expect($this->getJson(route('discover.search', ['filter' => ['software' => 'Figma']]))->json('data.0.slug'))->toBe($creative->slug);

    $looks = $this->getJson(route('discover.search', ['filter' => ['looks' => 'Editorial']]))->assertOk();
    expect($looks->json('meta.pagination.total'))->toBe(1);
    expect($looks->json('data.0.slug'))->toBe($model->slug);
});

it('filters by location — city and country (universal)', function () {
    $cairo = Talent::factory()->create(['base_city' => 'Cairo', 'base_country' => 'Egypt']);
    Talent::factory()->create(['base_city' => 'Dubai', 'base_country' => 'UAE']);

    expect($this->getJson(route('discover.search', ['filter' => ['city' => 'Cairo']]))->json('meta.pagination.total'))->toBe(1);
    expect($this->getJson(route('discover.search', ['filter' => ['country' => 'Egypt']]))->json('data.0.slug'))->toBe($cairo->slug);
});

it('still filters by the secondary free-text search (q)', function () {
    $nour = Talent::factory()->create(['display_name' => 'Nour Hassan']);
    Talent::factory()->create(['display_name' => 'Omar Ali']);

    $response = $this->getJson(route('discover.search', ['filter' => ['q' => 'Nour']]))->assertOk();

    expect($response->json('meta.pagination.total'))->toBe(1);
    expect($response->json('data.0.slug'))->toBe($nour->slug);
});

it('paginates filtered results (advanced filter applied + paging)', function () {
    Talent::factory()->count(15)->create(['base_country' => 'Egypt']);
    Talent::factory()->count(3)->create(['base_country' => 'UAE']);

    $page1 = $this->getJson(route('discover.search', ['filter' => ['country' => 'Egypt']]))->assertOk();
    expect($page1->json('data'))->toHaveCount(12);
    expect($page1->json('meta.pagination.total'))->toBe(15);

    $page2 = $this->getJson(route('discover.search', ['filter' => ['country' => 'Egypt'], 'page' => 2]))->assertOk();
    expect($page2->json('data'))->toHaveCount(3);
});

it('paginates results (12 per page)', function () {
    Talent::factory()->count(15)->create();

    $response = $this->getJson(route('discover.search'))->assertOk();

    expect($response->json('data'))->toHaveCount(12);
    expect($response->json('meta.pagination.last_page'))->toBe(2);
    expect($this->getJson(route('discover.search', ['page' => 2]))->json('data'))->toHaveCount(3);
});
