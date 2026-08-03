<?php

use App\Models\Comment;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Talent;
use Illuminate\Support\Facades\DB;

// ----- 5R sweep: every public read is guest-200, every write is guest-401 ----

it('serves every public feed read to guests', function () {
    $talent = Talent::factory()->create();
    $post = Post::factory()->byAuthor($talent)->create();
    $comment = Comment::factory()->create(['post_id' => $post->id]);

    foreach ([
        '/feed',
        '/feed/items',
        '/feed/casting',
        "/posts/{$post->id}",
        "/posts/{$post->id}/fragment",
        "/posts/{$post->id}/comments",
        "/comments/{$comment->id}/replies",
        "/talents/{$talent->slug}/posts",
        "/talents/{$talent->slug}/followers",
        "/talents/{$talent->slug}/following",
        '/mentions/search?q=ra',
        '/design/feed-tokens',
    ] as $url) {
        $this->getJson($url)->assertOk();
    }
});

it('answers every guest write with the 401 envelope', function () {
    $post = Post::factory()->create();
    $comment = Comment::factory()->create(['post_id' => $post->id]);
    $talent = Talent::factory()->create();

    foreach ([
        ['post', '/posts', ['post_type' => 'note', 'body' => 'x']],
        ['patch', "/posts/{$post->id}", ['body' => 'x']],
        ['delete', "/posts/{$post->id}", []],
        ['post', "/posts/{$post->id}/pin", []],
        ['post', "/posts/{$post->id}/like", []],
        ['delete', "/posts/{$post->id}/like", []],
        ['post', "/posts/{$post->id}/comments", ['body' => 'x']],
        ['patch', "/comments/{$comment->id}", ['body' => 'x']],
        ['delete', "/comments/{$comment->id}", []],
        ['post', "/comments/{$comment->id}/like", []],
        ['post', "/follow/talent/{$talent->id}", []],
        ['delete', "/follow/talent/{$talent->id}", []],
        ['post', '/links/preview', ['url' => 'https://instagram.com/x']],
        ['post', "/posts/{$post->id}/report", ['reason' => 'spam']],
        ['get', '/notifications', []],
    ] as [$method, $url, $body]) {
        $response = match ($method) {
            'post' => $this->postJson($url, $body),
            'patch' => $this->patchJson($url, $body),
            'delete' => $this->deleteJson($url),
            'get' => $this->getJson($url),
        };

        $response->assertStatus(401)->assertJsonPath('success', false);
    }
});

// ----- 5R: remaining fixed-query-count budgets --------------------------------

it('renders the post detail page within a fixed query budget', function () {
    $post = Post::factory()->create(['body' => 'Budget check']);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get("/posts/{$post->id}")->assertOk();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBeLessThan(15);
});

it('serves the casting strip within a fixed query budget', function () {
    $viewer = Talent::factory()->create();
    Talent::factory()->count(20)->create();
    Follow::factory()->by($viewer)->of(Talent::factory()->create())->create();

    // Warm the ranked-ID cache like a real session.
    $this->actingAs($viewer, 'talent')->getJson('/feed/casting')->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($viewer, 'talent')->getJson('/feed/casting')->assertOk();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBeLessThan(10);
});
