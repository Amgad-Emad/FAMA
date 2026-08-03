<?php

use App\Models\Brand;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\CounterService;
use App\Services\FollowManagementService;
use Illuminate\Support\Facades\DB;

/**
 * The dashboard FOLLOWING / FOLLOWERS management surface (Prompt 5-follow):
 * mixed-type cards shaped by type, the mutual + quiet-since signals, the
 * denormalized last_posted_at, filters/sorts, the privacy boundary against
 * the public listing, and the fixed-query-count eager-load contract.
 */

function follow(Talent|Brand $follower, Talent|Brand $followable, ?string $at = null): Follow
{
    return Follow::factory()->by($follower)->of($followable)->create(
        $at !== null ? ['created_at' => $at] : []
    );
}

// ----- Card shape + payload --------------------------------------------------

it('lists a talent\'s following mixed by type, carrying the type on each row', function () {
    $me = Talent::factory()->create();
    $followedTalent = Talent::factory()->create();
    $followedBrand = Brand::factory()->create();

    follow($me, $followedTalent);
    follow($me, $followedBrand);

    $rows = collect(
        $this->actingAs($me, 'talent')
            ->getJson('/talent/feed/follows/data?tab=following')
            ->assertOk()
            ->json('data')
    );

    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('type')->sort()->values()->all())->toBe(['brand', 'talent']);
});

it('shows the top two Skills for talents and sector + city for brands', function () {
    $me = Talent::factory()->create();

    $talent = Talent::factory()->create();
    TalentType::factory()->count(3)->create();
    $skills = TalentType::query()->orderBy('id')->take(3)->get();
    foreach ($skills as $i => $skill) {
        $talent->talentTypes()->attach($skill->id, ['is_primary' => $i === 0, 'position' => $i]);
    }

    $brand = Brand::factory()->create(['industry' => 'food_beverage', 'base_city' => 'Cairo']);

    follow($me, $talent);
    follow($me, $brand);

    $rows = collect(
        $this->actingAs($me, 'talent')->getJson('/talent/feed/follows/data?tab=following')->json('data')
    )->keyBy('type');

    expect($rows['talent']['skills'])->toHaveCount(2)
        ->and($rows['brand']['sector'])->toBe('food_beverage')
        ->and($rows['brand']['city'])->toBe('Cairo');
});

// ----- Mutual + quiet-since signals ------------------------------------------

it('flags FOLLOWS YOU BACK only when the edge is mutual', function () {
    $me = Talent::factory()->create();
    $mutualFriend = Talent::factory()->create();
    $oneWay = Talent::factory()->create();

    follow($me, $mutualFriend);
    follow($mutualFriend, $me); // returns the follow
    follow($me, $oneWay);

    $rows = collect(
        $this->actingAs($me, 'talent')->getJson('/talent/feed/follows/data?tab=following')->json('data')
    )->keyBy('id');

    expect($rows[$mutualFriend->id]['mutual'])->toBeTrue()
        ->and($rows[$oneWay->id]['mutual'])->toBeFalse();
});

it('marks a talent quiet-since when silent past 90 days, and silent when never posted', function () {
    $me = Talent::factory()->create();

    $active = Talent::factory()->create();
    Post::factory()->byAuthor($active)->create(['published_at' => now()->subDays(3)]);

    $quiet = Talent::factory()->create();
    Post::factory()->byAuthor($quiet)->create(['published_at' => now()->subDays(200)]);

    $never = Talent::factory()->create();

    follow($me, $active);
    follow($me, $quiet);
    follow($me, $never);

    $rows = collect(
        $this->actingAs($me, 'talent')->getJson('/talent/feed/follows/data?tab=following')->json('data')
    )->keyBy('id');

    expect($rows[$active->id]['quiet_since'])->toBeNull()
        ->and($rows[$quiet->id]['quiet_since'])->toContain(now()->subDays(200)->isoFormat('MMMM'))
        ->and($rows[$never->id]['quiet_since'])->toBe(__('Never posted'));
});

// ----- last_posted_at maintenance --------------------------------------------

it('stamps last_posted_at on publish and clears it when the last post is deleted', function () {
    $talent = Talent::factory()->create();
    expect($talent->fresh()->last_posted_at)->toBeNull();

    $post = Post::factory()->byAuthor($talent)->create(['published_at' => now()->subDay()]);
    expect($talent->fresh()->last_posted_at)->not->toBeNull();

    $newer = Post::factory()->byAuthor($talent)->create(['published_at' => now()]);
    // Deleting the OLDER post leaves the newer stamp intact.
    $post->delete();
    expect($talent->fresh()->last_posted_at->timestamp)->toBe($newer->published_at->timestamp);

    // Deleting the last remaining post clears the column.
    $newer->delete();
    expect($talent->fresh()->last_posted_at)->toBeNull();
});

it('reconciles last_posted_at from source', function () {
    $talent = Talent::factory()->create();
    Post::factory()->byAuthor($talent)->create(['published_at' => now()->subDays(5)]);

    // Corrupt the denormalized value, then repair.
    DB::table('talents')->where('id', $talent->id)->update(['last_posted_at' => null]);

    $repaired = app(CounterService::class)->reconcile();

    expect($repaired['talents.last_posted_at'])->toBeGreaterThanOrEqual(1)
        ->and($talent->fresh()->last_posted_at)->not->toBeNull();
});

// ----- Filters + sorts -------------------------------------------------------

it('searches display name and username server-side across the whole set', function () {
    $me = Talent::factory()->create();
    $match = Talent::factory()->create(['display_name' => 'Rana El-Sherif']);
    Talent::factory()->count(3)->create()->each(fn ($t) => follow($me, $t));
    follow($me, $match);

    $rows = collect(
        $this->actingAs($me, 'talent')->getJson('/talent/feed/follows/data?tab=following&q=rana')->json('data')
    );

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['id'])->toBe($match->id);
});

it('sorts least-active by last_posted_at ascending, surfacing prune candidates first', function () {
    $me = Talent::factory()->create();

    $recent = Talent::factory()->create();
    Post::factory()->byAuthor($recent)->create(['published_at' => now()->subDays(2)]);
    $old = Talent::factory()->create();
    Post::factory()->byAuthor($old)->create(['published_at' => now()->subDays(300)]);
    $never = Talent::factory()->create();

    follow($me, $recent);
    follow($me, $old);
    follow($me, $never);

    $ids = collect(
        $this->actingAs($me, 'talent')->getJson('/talent/feed/follows/data?tab=following&sort=least_active')->json('data')
    )->pluck('id');

    // Never-posted (quietest) first, then oldest post, then recent.
    expect($ids->first())->toBe($never->id)
        ->and($ids->last())->toBe($recent->id);
});

it('only offers Skills present in the follow set, not the full catalog', function () {
    $me = Talent::factory()->create();
    $talent = Talent::factory()->create();
    $skill = TalentType::query()->first() ?? TalentType::factory()->create();
    $talent->talentTypes()->attach($skill->id, ['is_primary' => true, 'position' => 0]);
    follow($me, $talent);

    $present = app(FollowManagementService::class)->presentSkills($me, 'followable');

    expect($present)->toHaveCount(1)
        ->and($present[0]['slug'])->toBe($skill->slug);
});

// ----- Followers tab ---------------------------------------------------------

it('offers FOLLOW BACK on the followers tab only where not mutual, never unfollow', function () {
    $me = Talent::factory()->create();
    $fan = Talent::factory()->create();
    follow($fan, $me);

    $row = collect(
        $this->actingAs($me, 'talent')->getJson('/talent/feed/follows/data?tab=followers')->json('data')
    )->firstWhere('id', $fan->id);

    expect($row['can_follow_back'])->toBeTrue()
        ->and($row['can_unfollow'])->toBeFalse();
});

// ----- Brand surface ---------------------------------------------------------

it('hides the type filter for brands, who follow only talents', function () {
    $brand = Brand::factory()->create();
    $talent = Talent::factory()->create();
    follow($brand, $talent);

    $this->actingAs($brand, 'brand')
        ->get('/brand/feed/follows')
        ->assertOk()
        ->assertViewHas('showTypeFilter', false);

    // And the talent surface DOES enable it.
    $this->actingAs(Talent::factory()->create(), 'talent')
        ->get('/talent/feed/follows')
        ->assertOk()
        ->assertViewHas('showTypeFilter', true);
});

// ----- Privacy: dashboard signals never leak to the public listing -----------

it('never exposes the mutual badge or quiet-since in the public follow listing', function () {
    $talent = Talent::factory()->create(['is_published' => true]);
    $followed = Talent::factory()->create(['is_published' => true]);
    Post::factory()->byAuthor($followed)->create(['published_at' => now()->subDays(200)]);
    follow($talent, $followed);
    follow($followed, $talent); // mutual

    $row = collect(
        $this->getJson("/talents/{$talent->slug}/following")->assertOk()->json('data')
    )->first();

    expect($row)->not->toHaveKey('mutual')
        ->and($row)->not->toHaveKey('quiet_since')
        ->and($row)->not->toHaveKey('followed_at');
});

// ----- Performance: fixed query count, no morph N+1 --------------------------

it('runs a fixed number of queries regardless of page size', function () {
    $me = Talent::factory()->create();

    $countFor = function (int $n) use ($me): int {
        // Reset the graph to exactly N mixed follows.
        Follow::query()->where('follower_id', $me->id)->where('follower_type', 'talent')->delete();
        for ($i = 0; $i < $n; $i++) {
            $target = $i % 2 === 0 ? Talent::factory()->create() : Brand::factory()->create();
            if ($target instanceof Talent) {
                $skill = TalentType::query()->first() ?? TalentType::factory()->create();
                $target->talentTypes()->attach($skill->id, ['is_primary' => true, 'position' => 0]);
            }
            follow($me, $target);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($me, 'talent')->getJson('/talent/feed/follows/data?tab=following')->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };

    // Same query count for 3 and 15 followed accounts — the morphWith eager
    // load (talents+Skills+media, brands+logo) does not grow per row.
    expect($countFor(3))->toBe($countFor(15));
});
