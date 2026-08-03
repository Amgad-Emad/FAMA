<?php

use App\Actions\Feed\SyncLinks;
use App\Feed\Links\ChipLabel;
use App\Models\Comment;
use App\Models\LinkPreview;
use App\Models\Post;
use App\Services\BodyRenderer;
use App\Services\RichTextParser;

/**
 * Render a comment body through the full pipeline: parse → sync → render.
 */
function renderBody(string $body): string
{
    $comment = Comment::factory()->create(['body' => $body]);
    app(SyncLinks::class)($comment, app(RichTextParser::class)->parse($body));

    return app(BodyRenderer::class)->render(
        $comment->fresh(['mentions.mentioned', 'linkedUrls.preview'])
    );
}

// ----- 5V: the raw URL is REPLACED by the chip -------------------------------

it('replaces a raw url with a glyph + handle chip — never shows the url text', function (string $body, string $expectedLabel, string $glyph) {
    $html = renderBody($body);

    expect($html)->toContain('feed-chip')
        ->toContain('#glyph-'.$glyph)
        ->toContain($expectedLabel)
        ->not->toContain('>https://<');

    // The visible text never includes the raw URL.
    expect(strip_tags($html))->not->toContain('instagram.com')
        ->not->toContain('tiktok.com');
})->with([
    'instagram profile + tracking query' => ['Find me https://www.instagram.com/amgad/?igsh=track123', '@amgad', 'instagram'],
    'instagram post' => ['New drop https://instagram.com/p/Cxyz9/', 'Instagram post', 'instagram'],
    'tiktok video, mobile subdomain' => ['BTS https://m.tiktok.com/@rana/video/999?lang=en', 'TikTok video', 'tiktok'],
    'trailing slash profile' => ['https://www.instagram.com/rana/', '@rana', 'instagram'],
]);

it('bdi-wraps a chip inside Arabic text', function () {
    $html = renderBody('شاهدوا الجلسة كاملة https://instagram.com/p/AbC123 قريباً');

    expect($html)->toContain('<bdi><a href=')
        ->toContain('Instagram post')
        ->toContain('قريباً');
});

it('handles adjacent urls and urls at both ends of the body', function () {
    $html = renderBody('https://instagram.com/rana https://www.behance.net/rana');

    expect(substr_count($html, 'feed-chip'))->toBe(2)
        ->and($html)->toContain('#glyph-instagram')
        ->toContain('#glyph-behance');

    // URL at the very end, no trailing text.
    $end = renderBody('See https://dribbble.com/shots/42-cover');
    expect($end)->toContain('Dribbble shot');
});

it('keeps a useful chip when enrichment failed permanently', function () {
    $comment = Comment::factory()->create(['body' => 'watch https://youtu.be/abc123']);
    app(SyncLinks::class)($comment, app(RichTextParser::class)->parse($comment->body));

    LinkPreview::query()->update(['status' => 'failed']);

    $html = app(BodyRenderer::class)->render($comment->fresh(['mentions.mentioned', 'linkedUrls.preview']));

    expect($html)->toContain('YouTube video')
        ->not->toContain('chip-preview');
});

it('prefers the enriched title for content links and renders the hover preview', function () {
    $comment = Comment::factory()->create(['body' => 'watch https://youtu.be/abc123']);
    app(SyncLinks::class)($comment, app(RichTextParser::class)->parse($comment->body));

    LinkPreview::query()->update([
        'status' => 'ok',
        'title' => 'Fama BTS — Night Shoot',
        'image_url' => 'https://i.ytimg.com/vi/abc/hq.jpg',
    ]);

    $html = app(BodyRenderer::class)->render($comment->fresh(['mentions.mentioned', 'linkedUrls.preview']));

    expect($html)->toContain('Fama BTS — Night Shoot')
        ->toContain('chip-preview')
        ->toContain('i.ytimg.com');
});

it('labels a generic url with the registrable domain', function () {
    $preview = LinkPreview::query()->create([
        'url_hash' => hash('sha256', 'https://www.studio-x.example/work'),
        'url' => 'https://www.studio-x.example/work',
        'provider' => 'generic',
        'status' => 'pending',
    ]);

    expect(ChipLabel::for($preview))->toBe('studio-x.example');
});

// ----- The API's structured chips array --------------------------------------

it('returns a structured chips array alongside body_html', function () {
    $talent = App\Models\Talent::factory()->create();
    $post = Post::factory()->byAuthor($talent)->create(['body' => 'https://instagram.com/rana']);
    app(SyncLinks::class)($post, app(RichTextParser::class)->parse($post->body));

    $this->getJson("/api/v1/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('data.chips.0.provider', 'instagram')
        ->assertJsonPath('data.chips.0.handle', 'rana');
});
