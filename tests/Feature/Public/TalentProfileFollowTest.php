<?php

use App\Actions\Feed\ToggleFollow;
use App\Models\Brand;
use App\Models\Talent;

/**
 * The public talent profile (Prompt: follow-from-profile) exposes a Follow
 * toggle wired to the feed follow endpoints, reflecting the viewer's state and
 * hidden on the viewer's own profile.
 */

function publishedTalent(): Talent
{
    return Talent::factory()->create(['is_published' => true]);
}

it('shows a Follow button to a guest', function () {
    $talent = publishedTalent();

    $html = $this->get("/{$talent->slug}")->assertOk()->getContent();

    expect($html)->toContain('talentFollow(')
        ->toContain('following: false');
});

it('reflects the existing follow state for a signed-in actor', function () {
    $talent = publishedTalent();
    $brand = Brand::factory()->create(['is_published' => true]);
    app(ToggleFollow::class)($brand, $talent, onlyFollow: true);

    $html = $this->actingAs($brand, 'brand')->get("/{$talent->slug}")->assertOk()->getContent();

    expect($html)->toContain('following: true');
});

it('hides the Follow button on the viewer’s own profile', function () {
    $talent = publishedTalent();

    $html = $this->actingAs($talent, 'talent')->get("/{$talent->slug}")->assertOk()->getContent();

    expect($html)->not->toContain('talentFollow(');
});

it('lets a signed-in actor follow the talent through the endpoint', function () {
    $talent = publishedTalent();
    $follower = Talent::factory()->create();

    $this->actingAs($follower, 'talent')
        ->postJson("/follow/talent/{$talent->id}")
        ->assertOk();

    expect($talent->fresh()->followers_count)->toBe(1);
});
