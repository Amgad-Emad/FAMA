<?php

use App\Actions\Feed\ToggleFollow;
use App\Models\Brand;
use App\Models\Follow;
use App\Models\Talent;
use App\Services\FollowService;

// ----- Idempotency -----------------------------------------------------------

it('keeps a single edge under repeated follow calls', function () {
    $fan = Talent::factory()->create();
    $talent = Talent::factory()->create();

    $toggle = app(ToggleFollow::class);
    $toggle($fan, $talent, onlyFollow: true);
    $toggle($fan, $talent, onlyFollow: true);
    $toggle($fan, $talent, onlyFollow: true);

    expect(Follow::query()->count())->toBe(1)
        ->and($talent->refresh()->followers_count)->toBe(1)
        ->and($fan->refresh()->following_count)->toBe(1);

    // Unfollow is idempotent too.
    $toggle($fan, $talent, onlyUnfollow: true);
    $toggle($fan, $talent, onlyUnfollow: true);

    expect(Follow::query()->count())->toBe(0)
        ->and($talent->refresh()->followers_count)->toBe(0);
});

// ----- Bulk follow-state map -------------------------------------------------

it('answers follow-state for a mixed talent and brand set in one keyed map', function () {
    config(['feed.allow_following_brands' => true]);

    $viewer = Talent::factory()->create();
    $followedTalent = Talent::factory()->create();
    $unfollowedTalent = Talent::factory()->create();
    $followedBrand = Brand::factory()->create();

    app(ToggleFollow::class)($viewer, $followedTalent, onlyFollow: true);
    app(ToggleFollow::class)($viewer, $followedBrand, onlyFollow: true);

    $map = app(FollowService::class)->isFollowing($viewer, collect([
        $followedTalent, $unfollowedTalent, $followedBrand,
    ]));

    expect($map["talent:{$followedTalent->id}"])->toBeTrue()
        ->and($map["talent:{$unfollowedTalent->id}"])->toBeFalse()
        ->and($map["brand:{$followedBrand->id}"])->toBeTrue();

    // Guests get an all-false map with the same keys.
    $guestMap = app(FollowService::class)->isFollowing(null, collect([$followedTalent]));
    expect($guestMap)->toBe(["talent:{$followedTalent->id}" => false]);
});

// ----- Audience listings -----------------------------------------------------

it('lists followers paginated 24 with the viewer follow-state flag', function () {
    $talent = Talent::factory()->create();
    $fans = Talent::factory()->count(30)->create();
    // Distinct timestamps — the listing orders by created_at, and identical
    // values would make page-1 membership nondeterministic.
    $fans->each(fn ($fan, $i) => Follow::factory()->by($fan)->of($talent)
        ->create(['created_at' => now()->subMinutes($i)]));

    $viewer = $fans->first();
    // The viewer follows one of the other fans, so a flag must light up.
    Follow::factory()->by($viewer)->of($fans[1])->create();

    $response = $this->actingAs($viewer, 'talent')
        ->getJson("/talents/{$talent->slug}/followers")
        ->assertOk();

    $rows = collect($response->json('data'));

    expect($rows)->toHaveCount(24)
        ->and($response->json('meta.pagination.total'))->toBe(30)
        ->and($rows->firstWhere('id', $fans[1]->id)['followed_by_viewer'] ?? false)->toBeTrue();
});

it('lists following and 404s an unpublished talent audience', function () {
    $talent = Talent::factory()->create();
    $peer = Talent::factory()->create();
    Follow::factory()->by($talent)->of($peer)->create();

    $rows = collect($this->getJson("/talents/{$talent->slug}/following")->assertOk()->json('data'));

    expect($rows->pluck('id'))->toContain($peer->id);

    $draft = Talent::factory()->draft()->create();
    $this->getJson("/talents/{$draft->slug}/followers")->assertNotFound();
});
