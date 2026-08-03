<?php

use App\Actions\Feed\SyncLinks;
use App\Actions\Feed\SyncMentions;
use App\Models\Brand;
use App\Models\Comment;
use App\Models\LinkPreview;
use App\Models\Post;
use App\Models\Talent;
use App\Services\BodyRenderer;
use App\Services\RichTextParser;

// ----- Parsing (ADR-F3/F4) ---------------------------------------------------

it('parses mentions and urls with correct offsets in a Latin body', function () {
    Talent::factory()->create(['slug' => 'rana']);

    $parsed = app(RichTextParser::class)->parse('Shot with @rana — see https://instagram.com/rana!');

    expect($parsed->mentions)->toHaveCount(1)
        ->and($parsed->mentions[0]->mentionedType)->toBe('talent')
        ->and($parsed->mentions[0]->startOffset)->toBe(10)
        ->and($parsed->mentions[0]->length)->toBe(5)
        ->and($parsed->urls)->toHaveCount(1)
        ->and($parsed->urls[0]->url)->toBe('https://instagram.com/rana')
        ->and($parsed->urls[0]->startOffset)->toBe(22);
});

it('computes multibyte-correct character offsets in an Arabic body', function () {
    Talent::factory()->create(['slug' => 'rana']);

    $raw = 'تصوير رائع من @rana في القاهرة';
    $parsed = app(RichTextParser::class)->parse($raw);

    expect($parsed->mentions)->toHaveCount(1)
        ->and($parsed->mentions[0]->startOffset)->toBe(14)
        ->and($parsed->mentions[0]->length)->toBe(5)
        // The offset addresses the token in CHARACTERS — slicing there yields it.
        ->and(mb_substr($raw, 14, 5))->toBe('@rana');
});

it('resolves talents before brands and leaves unknown tokens plain', function () {
    Talent::factory()->create(['slug' => 'shared-name']);
    Brand::factory()->create(['slug' => 'shared-name']);
    Brand::factory()->create(['slug' => 'brand-only']);

    $parsed = app(RichTextParser::class)->parse('@shared-name @brand-only @nobody-here');

    expect($parsed->mentions)->toHaveCount(2)
        ->and($parsed->mentions[0]->mentionedType)->toBe('talent')
        ->and($parsed->mentions[1]->mentionedType)->toBe('brand');
});

it('never turns an e-mail address into a mention', function () {
    Talent::factory()->create(['slug' => 'example']);

    $parsed = app(RichTextParser::class)->parse('write to rana@example.com please');

    expect($parsed->mentions)->toBeEmpty();
});

// ----- Sync actions ----------------------------------------------------------

it('syncs mentions and links idempotently and re-parses on edit', function () {
    $rana = Talent::factory()->create(['slug' => 'rana']);
    Talent::factory()->create(['slug' => 'hossam']);
    $post = Post::factory()->create();
    $comment = Comment::factory()->create(['post_id' => $post->id, 'body' => 'with @rana https://behance.net/x']);

    $parser = app(RichTextParser::class);
    $parsed = $parser->parse($comment->body);

    app(SyncMentions::class)($comment, $parsed);
    app(SyncLinks::class)($comment, $parsed);
    app(SyncMentions::class)($comment, $parsed); // idempotent second run
    app(SyncLinks::class)($comment, $parsed);

    expect($comment->mentions()->count())->toBe(1)
        ->and($comment->linkedUrls()->count())->toBe(1)
        ->and(LinkPreview::query()->count())->toBe(1);

    // Edit: body changes, full re-parse replaces the rows.
    $comment->update(['body' => 'now with @hossam only']);
    $reparsed = $parser->parse($comment->body);
    app(SyncMentions::class)($comment, $reparsed);
    app(SyncLinks::class)($comment, $reparsed);

    expect($comment->mentions()->count())->toBe(1)
        ->and($comment->mentions()->first()->mentioned_id)->not->toBe($rana->id)
        ->and($comment->linkedUrls()->count())->toBe(0);
});

it('rejects a comment exceeding the mention limit with a validation error naming it', function () {
    $post = Post::factory()->create();
    $talent = Talent::factory()->create();
    $slugs = Talent::factory()->count(6)->create()->pluck('slug');

    $body = $slugs->map(fn ($slug) => '@'.$slug)->implode(' ');

    $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/comments", ['body' => $body])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonFragment(['body' => [__('A :type may mention at most :limit accounts.', ['type' => 'comment', 'limit' => 5])]]);
});

it('rejects a body exceeding the url limit', function () {
    $post = Post::factory()->create();
    $talent = Talent::factory()->create();

    $body = collect(range(1, 6))->map(fn ($i) => "https://example.com/{$i}")->implode(' ');

    $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/comments", ['body' => $body])
        ->assertStatus(422);
});

// ----- Rendering (escape-first + bidi isolation) -----------------------------

it('renders safe html: escaped body, current-username mention, isolated tokens', function () {
    $rana = Talent::factory()->create(['slug' => 'rana']);
    $post = Post::factory()->create();
    $comment = Comment::factory()->create([
        'post_id' => $post->id,
        'body' => '<script>alert(1)</script> تصوير مع @rana و https://instagram.com/rana',
    ]);

    $parsed = app(RichTextParser::class)->parse($comment->body);
    app(SyncMentions::class)($comment, $parsed);
    app(SyncLinks::class)($comment, $parsed);

    $html = app(BodyRenderer::class)->render($comment->fresh(['mentions.mentioned', 'linkedUrls.preview']));

    // Escape-first: the script tag is inert text.
    expect($html)->toContain('&lt;script&gt;')
        ->not->toContain('<script>');

    // The mention is a <bdi>-wrapped anchor with LRI…PDI isolates around it.
    expect($html)->toContain('<bdi><a href="'.route('talent.public', ['slug' => 'rana']).'" class="feed-mention">'."\u{2066}".'@rana'."\u{2069}".'</a></bdi>');

    // The link chip is isolated too.
    expect($html)->toContain('class="feed-chip"')
        ->toContain("\u{2066}");

    // Renaming the Username re-renders with the CURRENT slug (ADR-F3).
    $rana->update(['slug' => 'rana-kamel']);
    $rerendered = app(BodyRenderer::class)->render($comment->fresh(['mentions.mentioned', 'linkedUrls.preview']));

    expect($rerendered)->toContain('@rana-kamel');
});

// ----- Mention search --------------------------------------------------------

it('searches mentions grouped by type, published only, limit 8', function () {
    Talent::factory()->create(['slug' => 'rana-kamel', 'display_name' => 'Rana Kamel', 'is_published' => true]);
    Talent::factory()->create(['slug' => 'hidden-rana', 'is_published' => false]);
    Brand::factory()->create(['slug' => 'rana-studio', 'name' => 'Rana Studio', 'is_published' => true]);

    $response = $this->getJson('/mentions/search?q=rana')->assertOk();

    $talents = collect($response->json('data.talents'));
    $brands = collect($response->json('data.brands'));

    expect($talents->pluck('username'))->toContain('rana-kamel')
        ->not->toContain('hidden-rana')
        ->and($brands->pluck('username'))->toContain('rana-studio')
        ->and($talents->first())->toHaveKeys(['type', 'id', 'username', 'display_name', 'avatar_url']);
});
