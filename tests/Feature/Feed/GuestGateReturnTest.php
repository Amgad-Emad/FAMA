<?php

use App\Models\Like;
use App\Models\Post;
use App\Models\Talent;

// ----- The full guest loop (ADR-F8): gate → login → replay → Profile structure ------
// Product rule (2026-10-12): a talent ALWAYS lands on Profile structure after login;
// the visitor's pending action is still replayed by the Login event.

it('replays the guest action on login and lands the talent on Profile structure', function () {
    $talent = Talent::factory()->create(['password' => bcrypt('password')]);
    $post = Post::factory()->create();

    // Guest taps ✦ — 401 with the intent envelope; intent + return slot stored.
    $this->postJson("/posts/{$post->id}/like")
        ->assertStatus(401)
        ->assertJsonPath('data.intent.action', 'like');

    // Login replays the like and lands on the talent home.
    $response = $this->post('/login', [
        'email' => $talent->email, 'password' => 'password', 'role' => 'talent',
    ]);

    $response->assertRedirect(route('talent.profile.edit'));
    expect(session('feed.return_to'))->toBeNull();

    expect(Like::query()->where([
        'likeable_type' => 'post', 'likeable_id' => $post->id,
        'liker_type' => 'talent', 'liker_id' => $talent->id,
    ])->exists())->toBeTrue();
});

it('sends an ordinary talent login straight to Profile structure', function () {
    $talent = Talent::factory()->create(['password' => bcrypt('password')]);

    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent'])
        ->assertRedirect(route('talent.profile.edit'));
});
