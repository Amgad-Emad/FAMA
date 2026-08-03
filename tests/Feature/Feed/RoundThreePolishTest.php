<?php

use App\Models\Post;
use App\Models\PostReport;
use App\Models\Talent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ----- 5AB: fixed topbar + primary nav ---------------------------------------

it('renders the fixed topbar with the Feed-first nav and aria-current on the active item', function () {
    $html = $this->get('/feed')->assertOk()->getContent();

    expect($html)->toContain('site-topbar');

    // Nav order is Feed, Discover, Brands, Opportunities (round-3 default).
    $positions = collect(['Feed', 'Discover', 'Brands', 'Opportunities'])
        ->map(function (string $label) use ($html) {
            preg_match('/>\s*'.$label.'\b/u', $html, $m, PREG_OFFSET_CAPTURE);

            return $m[0][1] ?? false;
        });

    expect($positions->filter(fn ($p) => $p === false))->toBeEmpty()
        ->and($positions->values()->all())->toBe($positions->sort()->values()->all());

    // The active item is marked for assistive tech.
    expect($html)->toContain('aria-current="page"');
});

// ----- Component-tag @js regression (round 3) --------------------------------

it('never ships a literal Blade directive inside the rendered feed', function () {
    // @js() on a COMPONENT TAG attribute is not compiled by Blade — it reaches
    // the browser verbatim and breaks the whole frame's Alpine tree. This
    // guards the postFrame x-data (and any future attribute) against it.
    Post::factory()->count(3)->create();

    $html = $this->get('/feed')->assertOk()->getContent();

    expect($html)->not->toContain('@js(')
        ->and($html)->toContain('authorName:');
});

it('teleports the floating actions to <body> so position:fixed escapes the transformed feed ancestor', function () {
    // <main class="animate-fade-in-up"> animates a transform, which would
    // become the containing block for the fixed FAB stack and drop it below
    // the fold. The stack is teleported to body inside a daylight host.
    $html = $this->get('/feed')->assertOk()->getContent();

    expect($html)->toContain('class="fab-host"')
        ->toContain('Back to top')   // the back-to-top control
        ->toContain('NEW POST');     // the new-post control

    // The host is teleported (the template wraps it) and carries the daylight
    // scope so the scoped tokens/positioning resolve at body level.
    expect($html)->toMatch('/<template x-teleport="body">\s*<div data-surface="daylight" class="fab-host">/');
});

it('teleports the post overflow menu to <body> and puts bookmark in the top corner', function () {
    // The Daylight frame clips with clip-path, so an in-frame dropdown opens
    // invisibly — the overflow (⋯) menu is teleported. Bookmark is a top-corner
    // toggle on the frame; the standalone on-bar Save button is gone.
    Post::factory()->create();

    $html = $this->get('/feed')->assertOk()->getContent();

    expect($html)->toContain('overflowMenu()')          // the teleporting menu component
        ->toContain('x-ref="menu"')                     // its teleported panel
        ->toContain('bookmark-corner')                  // bookmark lives in the post's top corner
        ->not->toContain('Save this post by');          // the old on-bar Save button is gone
});

it('defines the famaLang JS string bridge on feed pages', function () {
    $html = $this->get('/feed')->assertOk()->getContent();

    expect($html)->toContain('window.famaLang')
        ->toContain('closeComposerTitle');
});

// ----- 5AF: widened report reasons -------------------------------------------

it('accepts every configured report reason', function (string $reason) {
    // Keep the dataset in lockstep with config — Pest datasets resolve before
    // the app boots, so config() can't be read there.
    expect(config('feed.report_reasons'))->toContain($reason);

    $talent = Talent::factory()->create();
    $post = Post::factory()->create();

    $payload = ['reason' => $reason] + ($reason === 'other' ? ['note' => 'Context'] : []);

    $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/report", $payload)
        ->assertCreated();

    expect(PostReport::query()->where('reason', $reason)->count())->toBe(1);
})->with(['spam', 'nudity', 'harassment', 'hate', 'impersonation', 'ip', 'other']);

it('offers exactly the seven round-3 report reasons', function () {
    expect(config('feed.report_reasons'))
        ->toBe(['spam', 'nudity', 'harassment', 'hate', 'impersonation', 'ip', 'other']);
});

it('requires a note when the reason is "other"', function () {
    $talent = Talent::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/report", ['reason' => 'other'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('note');
});

// ----- 5AI: per-image ALT text persists to the media library -----------------

it('stores composer ALT text as a custom property on each media item, in order', function () {
    Storage::fake('public');
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')
        ->post('/posts', [
            'post_type' => 'media',
            'body' => 'Alt pipeline',
            'media' => [
                UploadedFile::fake()->image('one.jpg', 800, 1000),
                UploadedFile::fake()->image('two.jpg', 800, 1000),
            ],
            'media_alt' => ['Model in silk, window light', ''],
        ])
        ->assertCreated();

    $media = Post::query()->latest('id')->first()->getMedia('post_media');

    expect($media)->toHaveCount(2)
        ->and($media[0]->getCustomProperty('alt'))->toBe('Model in silk, window light')
        ->and($media[1]->getCustomProperty('alt'))->toBeNull();
});

it('rejects ALT text over 160 characters', function () {
    Storage::fake('public');
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')
        ->postJson('/posts', [
            'post_type' => 'media',
            'media' => [UploadedFile::fake()->image('one.jpg')],
            'media_alt' => [str_repeat('a', 161)],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('media_alt.0');
});
