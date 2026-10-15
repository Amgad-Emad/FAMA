<?php

use App\Models\Post;
use App\Models\Talent;

it('sends a visitor to login when the feed is empty', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('sends a signed-in talent to their home when the feed is empty', function () {
    $this->actingAs(Talent::factory()->create(), 'talent')
        ->get('/')->assertRedirect(route('dashboard'));
});

it('opens the feed when there are posts to show', function () {
    Post::factory()->create();

    $this->get('/')->assertRedirect(route('feed'));
});
