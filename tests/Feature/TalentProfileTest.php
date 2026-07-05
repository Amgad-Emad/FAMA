<?php

use App\Models\Talent;

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
