<?php

use App\Models\Brand;
use App\Models\Post;
use App\Models\Talent;

/*
 * Share to your feed (ADR-AM, prototype posts.js repostDialog): a `repost`
 * post pointing at the original, with optional words; a repost of a repost
 * shares the original; the card links back, or says the original is gone.
 */

it('reposts a post to your feed with your words, and the card links to the original', function () {
    $original = Post::factory()->create(['body' => 'Golden hour on the Red Sea set.']);
    $talent = Talent::factory()->create(['is_published' => true]);

    $id = $this->actingAs($talent, 'talent')
        ->postJson(route('posts.repost', $original), ['body' => 'This light!'])
        ->assertCreated()->json('data.id');

    $repost = Post::findOrFail($id);
    expect($repost->post_type)->toBe('repost')
        ->and((int) $repost->reposted_post_id)->toBe($original->id)
        ->and($repost->body)->toBe('This light!');

    $html = $this->getJson(route('posts.fragment', $repost))->assertOk()->json('data.html');
    expect($html)->toContain('class="rp"')
        ->toContain('Golden hour on the Red Sea set.')
        ->toContain('href="'.route('posts.show', $original).'"');
});

it('shares the original when you repost a repost, and words are optional', function () {
    $original = Post::factory()->create();
    $first = Talent::factory()->create(['is_published' => true]);
    $second = Talent::factory()->create(['is_published' => true]);

    $repostId = $this->actingAs($first, 'talent')->postJson(route('posts.repost', $original))->assertCreated()->json('data.id');
    $again = $this->actingAs($second, 'talent')->postJson(route('posts.repost', Post::find($repostId)))->assertCreated()->json('data.id');

    expect((int) Post::find($again)->reposted_post_id)->toBe($original->id)
        ->and(Post::find($again)->body)->toBeNull();
});

it('keeps the repost when the original is deleted, saying it is no longer available', function () {
    $original = Post::factory()->create();
    $talent = Talent::factory()->create(['is_published' => true]);
    $id = $this->actingAs($talent, 'talent')->postJson(route('posts.repost', $original), ['body' => 'Look'])->json('data.id');

    $original->forceDelete();

    expect($this->getJson(route('posts.fragment', $id))->assertOk()->json('data.html'))->toContain('This post is no longer available.');
});

it('asks a visitor to sign in, and refuses brands (posting is talent-only)', function () {
    $original = Post::factory()->create();

    $this->postJson(route('posts.repost', $original))->assertStatus(401);
    $this->actingAs(Brand::factory()->create(), 'brand')->postJson(route('posts.repost', $original))->assertForbidden();
});

it('offers Share to your feed in a signed-in viewer’s share menu only', function () {
    Post::factory()->create();

    $this->get('/feed')->assertOk()->assertDontSee('openRepost()', false);
    $this->actingAs(Talent::factory()->create(), 'talent')->get('/feed')->assertOk()
        ->assertSee('openRepost()', false)
        ->assertSee('famaRepostDialog()', false);
});
