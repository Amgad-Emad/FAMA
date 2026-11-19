<?php

use App\Models\Post;
use App\Models\PostReport;
use App\Models\Talent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ----- fama-ui shell (2026-10-12, replaces the 5AB topbar) -------------------

it('renders the fama-ui header with the Feed link marked current', function () {
    $html = $this->get('/feed')->assertOk()->getContent();

    // A visitor gets the public header: logo · Feed | Log in.
    expect($html)->toContain('class="fx-nav"')
        ->toMatch('/<a href="[^"]*\/feed"\s+aria-current="page"\s*>Feed<\/a>/');
});

// ----- Component-tag @js regression (round 3) --------------------------------

it('never ships a literal Blade directive inside the rendered feed', function () {
    // @js() on a COMPONENT TAG attribute is not compiled by Blade — it reaches
    // the browser verbatim and breaks the whole card's Alpine tree. This
    // guards the postFrame x-data (and any future attribute) against it.
    Post::factory()->count(3)->create();

    $html = $this->get('/feed')->assertOk()->getContent();

    expect($html)->not->toContain('@js(')
        ->and($html)->toContain('postFrame(')
        ->and($html)->toContain('authorName');
});

it('renders the floating actions: Filters and Back to top for everyone, New post for a talent', function () {
    $guest = $this->get('/feed')->assertOk()->getContent();

    expect($guest)->toContain('class="fab" id="fab"')
        ->toContain('aria-label="Back to top"')
        ->toContain('id="fabFilter"')
        ->not->toContain('id="fabNew"'); // a visitor cannot post

    $talent = Talent::factory()->create();
    $html = $this->actingAs($talent, 'talent')->get('/feed')->assertOk()->getContent();

    expect($html)->toContain('id="fabNew"')
        ->toContain('aria-label="New post"')
        ->toContain('class="composer" id="composer"');
});

it('renders the post card with its More menu in place and Save on the action row', function () {
    Post::factory()->create();

    $html = $this->get('/feed')->assertOk()->getContent();

    expect($html)->toContain('class="more-wrap"')          // the More (⋯) menu lives in the card header
        ->toContain('class="pmenu"')
        ->toContain('class="pacts"')                        // Like · Comment · Share · Save
        ->toContain('aria-label="Save"')
        ->toContain('class="pmenu smenu"');                 // the share menu opens above the row
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

it('lets every comment under a feed post take a reply, threaded under it (13 Nov 2026)', function () {
    Post::factory()->create();

    $html = $this->get('/feed')->assertOk()->getContent();

    expect($html)->toContain('class="cthread"')              // a comment and its replies read as one thread
        ->toContain('class="creplies"')                      // replies indent on a thin line
        ->toContain('openReply(c)')                          // the Reply link
        ->toContain('class="cadd crform"')                   // the reply box opens under that comment
        ->toContain('cancelReply(c)')                        // with Cancel beside it
        ->toContain('sendReply(c)');
});
