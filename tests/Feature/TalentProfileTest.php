<?php

use App\Models\Project;
use App\Models\Review;
use App\Models\Talent;
use App\Models\TalentType;

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

it('renders the Instagram-style header (avatar, @username, primary skill, stats, CTAs)', function () {
    $talent = Talent::factory()->create([
        'slug' => 'nadia',
        'display_name' => 'Nadia K',
        'is_published' => true,
        'view_count' => 1200,
        'bio' => ['en' => 'Editorial photographer.'],
    ]);
    $type = TalentType::factory()->create(['name' => ['en' => 'Photography'], 'slug' => 'photography']);
    $talent->talentTypes()->attach($type->id, ['is_primary' => true, 'position' => 0]);
    Project::factory()->count(3)->for($talent)->create();

    $this->get('/nadia')
        ->assertOk()
        ->assertSee('Nadia K')                 // display name
        ->assertSee('@nadia')                  // @username (slug)
        ->assertSee('NK')                      // circular avatar initials fallback (no cover image)
        ->assertSee('Photography')             // primary skill secondary line
        ->assertSee('Projects')                // stat labels
        ->assertSee('Views')
        ->assertSee('Editorial photographer.') // bio
        ->assertSee(__('Message'))             // primary CTA (replaces "Contact" — ADR-P)
        ->assertDontSee(__('Contact'))
        ->assertSee(__('Leave a review'));     // secondary CTA
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

it('shows the average rating only when there are approved reviews', function () {
    $talent = Talent::factory()->create(['slug' => 'reviewed', 'is_published' => true]);
    Review::factory()->for($talent)->create(['rating' => 4]);
    Review::factory()->for($talent)->create(['rating' => 5]);

    $this->get('/reviewed')->assertOk()->assertSee('4.5')->assertSee(__('Rating'));

    Talent::factory()->create(['slug' => 'unreviewed', 'is_published' => true]);
    $this->get('/unreviewed')->assertOk()->assertDontSee(__('Rating'));
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
