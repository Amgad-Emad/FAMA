<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostReport;
use App\Models\Talent;

// ----- Fragment + talent posts (web) -----------------------------------------

it('serves a post as an {html} fragment envelope', function () {
    $post = Post::factory()->create(['body' => 'Fragment me']);

    $response = $this->getJson("/posts/{$post->id}/fragment")->assertOk();

    expect($response->json('data.html'))->toContain('Fragment me')
        ->toContain('id="post-'.$post->id.'"');
});

it('lists a talent\'s published posts, pinned first', function () {
    $talent = Talent::factory()->create();
    $old = Post::factory()->byAuthor($talent)->create(['published_at' => now()->subDays(3)]);
    $pinned = Post::factory()->byAuthor($talent)->create(['published_at' => now()->subDays(9), 'is_pinned' => true]);
    Post::factory()->byAuthor($talent)->draft()->create();

    $rows = collect($this->getJson("/talents/{$talent->slug}/posts")->assertOk()->json('data'));

    expect($rows)->toHaveCount(2)
        ->and($rows->first()['id'])->toBe($pinned->id)
        ->and($rows->pluck('id'))->toContain($old->id);
});

// ----- Reports ---------------------------------------------------------------

it('accepts one report per target per actor, rejecting the duplicate', function () {
    $talent = Talent::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/report", ['reason' => 'spam', 'note' => 'Bot content'])
        ->assertCreated();

    expect(PostReport::query()->count())->toBe(1)
        ->and(PostReport::query()->first()->status)->toBe('open');

    $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/report", ['reason' => 'spam'])
        ->assertStatus(403);

    // Comments are reportable too.
    $comment = Comment::factory()->create();
    $this->actingAs($talent, 'talent')
        ->postJson("/comments/{$comment->id}/report", ['reason' => 'harassment'])
        ->assertCreated();
});

it('rejects an unknown report reason', function () {
    $talent = Talent::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/report", ['reason' => 'because'])
        ->assertStatus(422);
});

// ----- API v1 mirror (Sanctum) -----------------------------------------------

it('serves the api feed stream to guests and post detail as json', function () {
    $post = Post::factory()->create(['body' => 'API surface']);

    $this->getJson('/api/v1/feed/items')
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->getJson("/api/v1/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('data.body', 'API surface');
});

it('rejects unauthenticated api writes and accepts a sanctum token', function () {
    $post = Post::factory()->create();

    $this->postJson("/api/v1/posts/{$post->id}/like")->assertStatus(401);

    $talent = Talent::factory()->create();
    $token = $talent->createToken('mobile')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/posts/{$post->id}/like")
        ->assertOk()
        ->assertJsonPath('data.liked', true);

    expect($post->refresh()->likes_count)->toBe(1);
});

it('shares one validation pipeline between web and api', function () {
    $talent = Talent::factory()->create();
    $token = $talent->createToken('mobile')->plainTextToken;

    // The same substance rule as the web path (empty note = 422).
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/posts', ['post_type' => 'note', 'body' => ' '])
        ->assertStatus(422);
});
