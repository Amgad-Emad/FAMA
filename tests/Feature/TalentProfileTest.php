<?php

use App\Models\BlockType;
use App\Models\Project;
use App\Models\Review;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;

beforeEach(fn () => $this->withoutVite());

it('shows a published talent profile by slug', function () {
    Talent::factory()->create([
        'slug' => 'jane-doe',
        'display_name' => 'Jane Doe',
        'is_published' => true,
    ]);

    $this->get('/jane-doe')
        ->assertOk()
        ->assertSee('Jane Doe');
});

it('renders the profile hero (avatar, name, first skill, stats, CTAs)', function () {
    $talent = Talent::factory()->create([
        'slug' => 'nadia',
        'display_name' => 'Nadia K',
        'is_published' => true,
        'view_count' => 1200,
        'bio' => ['en' => 'Editorial photographer.'],
        'headline' => null,                   // no headline → the first skill is the line under the name
    ]);
    $type = TalentType::factory()->create(['name' => ['en' => 'Photography'], 'slug' => 'photography']);
    $talent->talentTypes()->attach($type->id, ['is_primary' => true, 'position' => 0]);
    Project::factory()->count(3)->for($talent)->create();

    $this->get('/nadia')
        ->assertOk()
        ->assertSee('Nadia K')                 // display name
        ->assertSee('NK')                      // circular avatar initials fallback (no cover image)
        ->assertSee('Photography')             // primary skill secondary line
        ->assertDontSee('ct-stats')            // no stats row (removed 2026-11-23)
        ->assertSee(__('Skills'))              // the skill chips' list label
        ->assertSee('Editorial photographer.') // bio
        ->assertSee(__('Message'))             // primary CTA (replaces "Contact" — ADR-P)
        ->assertDontSee(__('Contact'))
        ->assertSee(__('Recognise'));          // secondary CTA (reviews are off: features.reviews)
});

it('points the primary Message CTA at the reserved brand↔talent messaging route', function () {
    Talent::factory()->create(['slug' => 'nadia', 'is_published' => true]);

    $this->get('/nadia')
        ->assertOk()
        ->assertSee(route('brand.talents.message', ['talent' => 'nadia']), escape: false);
});

it('shows the pricing rate near the identity when set, and hides it otherwise', function () {
    Talent::factory()->create([
        'slug' => 'rated', 'is_published' => true,
        'rate_unit' => 'day', 'rate_amount' => 5000, 'rate_currency' => 'EGP',
    ]);
    $this->get('/rated')->assertOk()->assertSee('EGP 5,000');

    Talent::factory()->create(['slug' => 'no-rate', 'is_published' => true]);
    $this->get('/no-rate')->assertOk()->assertDontSee(__('Rate'));
});

it('shows the review average only in the reviews section, and only while reviews are on', function () {
    $this->seed(BlockTypeSeeder::class);
    $talent = Talent::factory()->create(['slug' => 'reviewed', 'is_published' => true]);
    $talent->profileBlocks()->create([
        'block_type_id' => BlockType::where('key', 'reviews')->firstOrFail()->id,
        'talent_type_id' => null, 'position' => 0, 'is_visible' => true, 'status' => 'visible', 'settings' => [],
    ]);
    Review::factory()->for($talent)->create(['rating' => 4]);
    Review::factory()->for($talent)->create(['rating' => 5]);

    // Recognitions replace reviews on the public page (features.reviews off).
    $this->get('/reviewed')->assertOk()
        ->assertDontSee('<b>4.5</b>', false)
        ->assertDontSee(__('from :n reviews', ['n' => 2]));

    config(['features.reviews' => true]);
    $this->get('/reviewed')->assertOk()->assertSee('<b>4.5</b>', false)->assertSee(__('from :n reviews', ['n' => 2]));
});

it('returns 404 for an unpublished (draft) talent', function () {
    Talent::factory()->draft()->create(['slug' => 'hidden-one']);

    $this->get('/hidden-one')->assertNotFound();
});

it('returns 404 for an unknown slug', function () {
    $this->get('/nobody-here')->assertNotFound();
});

it('increments the view count on each visit', function () {
    $talent = Talent::factory()->create([
        'slug' => 'viewed',
        'is_published' => true,
        'view_count' => 0,
    ]);

    $this->get('/viewed')->assertOk();

    expect($talent->fresh()->view_count)->toBe(1);
});

// Note: locale-prefixed URLs (e.g. /ar/{slug}) are verified against the live
// server, not here — mcamara registers the locale route group per request, and
// the test harness boots the app once under the default locale.
