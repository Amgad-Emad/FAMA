<?php

use App\Models\Brand;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\Talent;
use App\Models\User;

// ----- Guest read (ADR-F8) ---------------------------------------------------

it('lets a guest load the feed page', function () {
    Post::factory()->count(3)->create();

    $this->get('/feed')->assertOk();
});

it('lets a guest load a published post detail, and 404s a draft', function () {
    $published = Post::factory()->create();
    $draft = Post::factory()->draft()->create();

    $this->get("/posts/{$published->id}")->assertOk();
    $this->get("/posts/{$draft->id}")->assertNotFound();
});

it('lets a guest read a comment thread', function () {
    $post = Post::factory()->create();
    Comment::factory()->count(2)->create(['post_id' => $post->id]);

    $this->getJson("/posts/{$post->id}/comments")
        ->assertOk()
        ->assertJsonPath('success', true);
});

// ----- Guest write → 401 + intent envelope (ADR-F8) --------------------------

it('rejects a guest like with the 401 intent envelope', function () {
    $post = Post::factory()->create();

    $this->postJson("/posts/{$post->id}/like")
        ->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('data.intent.action', 'like')
        ->assertJsonPath('data.intent.target_type', 'post')
        ->assertJsonPath('data.intent.target_id', $post->id);
});

it('rejects a guest follow with the 401 intent envelope', function () {
    $talent = Talent::factory()->create();

    $this->postJson("/follow/talent/{$talent->id}")
        ->assertStatus(401)
        ->assertJsonPath('data.intent.action', 'follow')
        ->assertJsonPath('data.intent.target_type', 'talent')
        ->assertJsonPath('data.intent.target_id', $talent->id);
});

it('rejects a guest comment with the 401 intent envelope carrying the typed body', function () {
    $post = Post::factory()->create();

    $this->postJson("/posts/{$post->id}/comments", ['body' => 'حلو جداً — beautiful work'])
        ->assertStatus(401)
        ->assertJsonPath('data.intent.action', 'comment')
        ->assertJsonPath('data.intent.payload.body', 'حلو جداً — beautiful work');
});

it('gives an admin a 403, not an intent, on feed writes', function () {
    $admin = User::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($admin, 'admin')
        ->postJson("/posts/{$post->id}/like")
        ->assertStatus(403);
});

// ----- Authenticated writes --------------------------------------------------

it('lets a talent like and unlike a post, counters moving', function () {
    $talent = Talent::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/like")
        ->assertOk()
        ->assertJsonPath('data.liked', true)
        ->assertJsonPath('data.likes_count', 1);

    // Idempotent — a second like does not double-count.
    $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/like")
        ->assertJsonPath('data.likes_count', 1);

    $this->actingAs($talent, 'talent')
        ->deleteJson("/posts/{$post->id}/like")
        ->assertJsonPath('data.liked', false)
        ->assertJsonPath('data.likes_count', 0);
});

it('lets a brand follow a talent and forbids self-follow', function () {
    $brand = Brand::factory()->create();
    $talent = Talent::factory()->create();

    $this->actingAs($brand, 'brand')
        ->postJson("/follow/talent/{$talent->id}")
        ->assertOk()
        ->assertJsonPath('data.following', true)
        ->assertJsonPath('data.followers_count', 1);

    $this->actingAs($talent, 'talent')
        ->postJson("/follow/talent/{$talent->id}")
        ->assertStatus(403);
});

it('rejects following a brand while the config flag is off', function () {
    config(['feed.allow_following_brands' => false]); // 5Z flipped the default on
    $talent = Talent::factory()->create();
    $brand = Brand::factory()->create();

    $this->actingAs($talent, 'talent')
        ->postJson("/follow/brand/{$brand->id}")
        ->assertStatus(422);
});

it('lets an actor comment, and re-attaches a reply-to-a-reply with an @mention (ADR-F7)', function () {
    $post = Post::factory()->create();
    $talent = Talent::factory()->create();
    $replier = Talent::factory()->create();

    $top = Comment::factory()->byAuthor($talent)->create(['post_id' => $post->id]);
    $reply = Comment::factory()->byAuthor($replier)->replyTo($top)->create();

    $response = $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/comments", ['body' => 'Thanks!', 'parent_id' => $reply->id])
        ->assertCreated();

    $created = Comment::query()->findOrFail($response->json('data.id'));

    expect($created->parent_id)->toBe($top->id)
        ->and($created->body)->toBe('@'.$replier->slug.' Thanks!');
});

// ----- Pending-intent replay (ADR-F8) ----------------------------------------

it('replays a captured like after talent login', function () {
    $talent = Talent::factory()->create(['password' => bcrypt('password')]);
    $post = Post::factory()->create();

    $this->postJson("/posts/{$post->id}/like")->assertStatus(401);

    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent']);

    expect(Like::query()->where([
        'likeable_type' => 'post', 'likeable_id' => $post->id,
        'liker_type' => 'talent', 'liker_id' => $talent->id,
    ])->exists())->toBeTrue()
        ->and($post->refresh()->likes_count)->toBe(1);
});

it('replays a captured follow after brand login', function () {
    $brand = Brand::factory()->create(['password' => bcrypt('password')]);
    $talent = Talent::factory()->create();

    $this->postJson("/follow/talent/{$talent->id}")->assertStatus(401);

    $this->post('/login', ['email' => $brand->email, 'password' => 'password', 'role' => 'brand']);

    expect(Follow::query()->where([
        'follower_type' => 'brand', 'follower_id' => $brand->id,
        'followable_type' => 'talent', 'followable_id' => $talent->id,
    ])->exists())->toBeTrue()
        ->and($talent->refresh()->followers_count)->toBe(1);
});

it('replays a captured comment — body included — exactly once', function () {
    $talent = Talent::factory()->create(['password' => bcrypt('password')]);
    $post = Post::factory()->create();

    $this->postJson("/posts/{$post->id}/comments", ['body' => 'Stunning light in this set'])
        ->assertStatus(401);

    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent']);

    expect(Comment::query()->where('post_id', $post->id)->count())->toBe(1)
        ->and(Comment::query()->first()->body)->toBe('Stunning light in this set');

    // Logging in again must NOT replay a second time.
    $this->post('/logout');
    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent']);

    expect(Comment::query()->where('post_id', $post->id)->count())->toBe(1);
});
