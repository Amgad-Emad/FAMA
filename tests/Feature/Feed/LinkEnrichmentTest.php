<?php

use App\Feed\Links\UrlGuard;
use App\Jobs\FetchLinkPreviewJob;
use App\Models\LinkPreview;
use App\Models\Talent;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

// ----- SSRF guard (feed-spec §8 — mandatory) ---------------------------------

it('rejects private, loopback, link-local and non-http urls', function (string $url) {
    expect(fn () => app(UrlGuard::class)->assertSafe($url))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'loopback ip' => ['http://127.0.0.1/admin'],
    'private 10/8' => ['http://10.0.0.5/'],
    'private 172.16/12' => ['http://172.16.1.1/x'],
    'private 192.168/16' => ['http://192.168.1.10/router'],
    'link-local metadata' => ['http://169.254.169.254/latest/meta-data'],
    'ftp scheme' => ['ftp://example.com/file'],
    'localhost hostname' => ['http://localhost/secret'],
]);

it('blocks the job from ever connecting to an unsafe url', function () {
    Http::fake();

    $preview = LinkPreview::query()->create([
        'url_hash' => hash('sha256', 'http://127.0.0.1/x'),
        'url' => 'http://127.0.0.1/x',
        'provider' => 'generic',
        'status' => 'pending',
    ]);

    expect(fn () => (new FetchLinkPreviewJob($preview))->handle(app(UrlGuard::class)))
        ->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

// ----- Enrichment (best-effort, never user-facing) ---------------------------

it('enriches from open graph tags and stamps ok + expiry', function () {
    Http::fake([
        'https://portfolio.example/*' => Http::response(
            '<html><head><title>Fallback</title>'
            .'<meta property="og:title" content="Cairo Editorial — SS26" />'
            .'<meta property="og:description" content="A story in daylight" />'
            .'<meta property="og:image" content="https://cdn.example/cover.jpg" />'
            .'</head><body></body></html>'
        ),
    ]);

    $preview = LinkPreview::query()->create([
        'url_hash' => hash('sha256', 'https://portfolio.example/work'),
        'url' => 'https://portfolio.example/work',
        'provider' => 'generic',
        'status' => 'pending',
    ]);

    // The guard is stubbed — test hosts don't resolve public DNS.
    $guard = new class extends UrlGuard
    {
        public function assertSafe(string $url): void
        {
        }
    };

    (new FetchLinkPreviewJob($preview))->handle($guard);

    $preview->refresh();

    expect($preview->status)->toBe('ok')
        ->and($preview->title)->toBe('Cairo Editorial — SS26')
        ->and($preview->description)->toBe('A story in daylight')
        ->and($preview->image_url)->toBe('https://cdn.example/cover.jpg')
        ->and($preview->expires_at)->not->toBeNull();
});

it('enriches youtube via oembed and stores only a sanitized iframe', function () {
    Http::fake([
        'https://www.youtube.com/oembed*' => Http::response([
            'title' => 'Fama BTS — Night Shoot',
            'author_name' => 'Fama',
            'thumbnail_url' => 'https://i.ytimg.com/vi/abc/hq.jpg',
            'html' => '<iframe src="https://www.youtube.com/embed/abc" onload="alert(1)"></iframe><script>evil()</script>',
        ]),
    ]);

    $preview = LinkPreview::query()->create([
        'url_hash' => hash('sha256', 'https://www.youtube.com/watch?v=abc'),
        'url' => 'https://www.youtube.com/watch?v=abc',
        'provider' => 'youtube',
        'provider_resource_id' => 'abc',
        'status' => 'pending',
    ]);

    $guard = new class extends UrlGuard
    {
        public function assertSafe(string $url): void
        {
        }
    };

    (new FetchLinkPreviewJob($preview))->handle($guard);

    $preview->refresh();

    expect($preview->status)->toBe('ok')
        ->and($preview->title)->toBe('Fama BTS — Night Shoot')
        ->and($preview->embed_html)->toBe('<iframe src="https://www.youtube.com/embed/abc" loading="lazy" allowfullscreen frameborder="0"></iframe>')
        ->and($preview->embed_html)->not->toContain('script')
        ->not->toContain('onload');
});

it('marks a terminally-failed enrichment failed while keeping the chip', function () {
    $preview = LinkPreview::query()->create([
        'url_hash' => hash('sha256', 'https://instagram.com/amgad'),
        'url' => 'https://instagram.com/amgad',
        'provider' => 'instagram',
        'provider_handle' => 'amgad',
        'status' => 'pending',
    ]);

    (new FetchLinkPreviewJob($preview))->failed(new RuntimeException('blocked'));

    $preview->refresh();

    expect($preview->status)->toBe('failed')
        ->and($preview->provider_handle)->toBe('amgad');
});

// ----- The composer endpoint -------------------------------------------------

it('returns the instant chip payload on paste and queues enrichment', function () {
    Bus::fake();
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')
        ->postJson('/links/preview', ['url' => 'https://www.instagram.com/amgad/'])
        ->assertOk()
        ->assertJsonPath('data.provider', 'instagram')
        ->assertJsonPath('data.handle', 'amgad')
        ->assertJsonPath('data.status', 'pending');

    Bus::assertDispatched(FetchLinkPreviewJob::class);

    // Same URL again → same deduplicated row, no second job.
    $this->actingAs($talent, 'talent')
        ->postJson('/links/preview', ['url' => 'https://www.instagram.com/amgad/'])
        ->assertOk();

    expect(LinkPreview::query()->count())->toBe(1);
    Bus::assertDispatchedTimes(FetchLinkPreviewJob::class, 1);
});

it('rejects guests and invalid urls on the preview endpoint', function () {
    $this->postJson('/links/preview', ['url' => 'https://instagram.com/x'])
        ->assertStatus(401);

    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')
        ->postJson('/links/preview', ['url' => 'javascript:alert(1)'])
        ->assertStatus(422);
});
