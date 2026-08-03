<?php

use App\Models\Like;
use App\Models\Post;
use App\Models\Talent;

// ----- The full guest loop (5O, ADR-F8): gate → login → replay → anchor ------

it('returns the guest to the exact post anchor after login, action already landed', function () {
    $talent = Talent::factory()->create(['password' => bcrypt('password')]);
    $post = Post::factory()->create();

    // Guest taps ✦ — 401 with the intent envelope; intent + return slot stored.
    $this->postJson("/posts/{$post->id}/like")
        ->assertStatus(401)
        ->assertJsonPath('data.intent.action', 'like');

    // Login replays the like AND lands back on the post anchor.
    $response = $this->post('/login', [
        'email' => $talent->email, 'password' => 'password', 'role' => 'talent',
    ]);

    $response->assertRedirect(route('posts.show', $post).'#post-'.$post->id);

    expect(Like::query()->where([
        'likeable_type' => 'post', 'likeable_id' => $post->id,
        'liker_type' => 'talent', 'liker_id' => $talent->id,
    ])->exists())->toBeTrue();
});

it('sends an ordinary login to the dashboard — the feed return slot is feed-only', function () {
    $talent = Talent::factory()->create(['password' => bcrypt('password')]);

    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent'])
        ->assertRedirect(route('dashboard'));
});
