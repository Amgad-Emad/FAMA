<?php

use App\Models\Post;
use App\Models\Talent;

it('sends a visitor to login when the feed is empty', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('sends a signed-in talent with an unfinished profile to the guided onboarding (ADR-AR)', function () {
    $this->actingAs(Talent::factory()->create(), 'talent')
        ->get('/')->assertRedirect(route('talent.onboarding'));
});

it('opens the feed when there are posts to show', function () {
    Post::factory()->create();

    $this->get('/')->assertRedirect(route('feed'));
});
