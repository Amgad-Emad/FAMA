<?php

use App\Actions\Feed\ToggleFollow;
use App\Models\Brand;
use App\Models\Contract;
use App\Models\ContractFlow;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\SuggestionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// ----- 5Z: the talent→brand follow edge is now first-class -------------------

it('lets a talent follow a brand with the new followers counter moving', function () {
    $talent = Talent::factory()->create();
    $brand = Brand::factory()->create(['is_published' => true]);

    $this->actingAs($talent, 'talent')
        ->postJson("/follow/brand/{$brand->id}")
        ->assertOk()
        ->assertJsonPath('data.following', true)
        ->assertJsonPath('data.followers_count', 1);

    expect($brand->refresh()->followers_count)->toBe(1)
        ->and($talent->refresh()->following_count)->toBe(1);

    // The brand shows up in the talent's following listing.
    $rows = collect($this->getJson("/talents/{$talent->slug}/following")->assertOk()->json('data'));
    expect($rows->firstWhere('type', 'brand')['id'] ?? null)->toBe($brand->id);

    // And reconcile agrees.
    DB::table('brands')->update(['followers_count' => 9]);
    $this->artisan('feed:reconcile-counters')->assertSuccessful();
    expect($brand->refresh()->followers_count)->toBe(1);
});

// ----- Exclusions (all mandatory) --------------------------------------------

it('never suggests the viewer, the followed, the dismissed, or the unpublished', function () {
    $viewer = Talent::factory()->create();
    $followed = Talent::factory()->create();
    $dismissed = Talent::factory()->create();
    $draft = Talent::factory()->draft()->create();
    $suspended = Talent::factory()->create(['is_active' => false]);
    $fresh = Talent::factory()->create();

    app(ToggleFollow::class)($viewer, $followed, onlyFollow: true);

    $service = app(SuggestionService::class);
    $service->dismiss($viewer, 'talent', $dismissed->id);

    $ids = collect($service->talentsFor($viewer, 50)['items'])->pluck('id');

    expect($ids)->toContain($fresh->id)
        ->not->toContain($viewer->id)
        ->not->toContain($followed->id)
        ->not->toContain($dismissed->id)
        ->not->toContain($draft->id)
        ->not->toContain($suspended->id);
});

it('deprioritizes recently-shown suggestions below fresh ones, yet still fills the rail when only they remain', function () {
    // A small pool (e.g. few brands) must NOT empty out once every candidate
    // has been shown once — the shown-rest is soft, not a hard exclusion.
    $viewer = Talent::factory()->create();
    $fresh = Talent::factory()->create();
    $shown = Talent::factory()->create();

    $service = app(SuggestionService::class);
    $service->markShown($viewer, 'talents', [$shown->id]);
    Cache::flush();                                         // drop the ranked cache…
    $service->markShown($viewer, 'talents', [$shown->id]);  // …keep the shown set

    $ids = collect($service->talentsFor($viewer, 50)['items'])->pluck('id');

    // Both appear; fresh outranks the recently-shown one.
    expect($ids)->toContain($fresh->id)->toContain($shown->id)
        ->and($ids->search($fresh->id))->toBeLessThan($ids->search($shown->id));

    // Follow the fresh one away — the shown one is now all that's left and
    // still fills the rail rather than vanishing.
    app(ToggleFollow::class)($viewer, $fresh, onlyFollow: true);
    Cache::flush();
    $service->markShown($viewer, 'talents', [$shown->id]);

    $remaining = collect($service->talentsFor($viewer, 50)['items'])->pluck('id');
    expect($remaining)->toContain($shown->id)->not->toContain($fresh->id);
});

// ----- Ranking + truthful reasons --------------------------------------------

it('ranks second-degree connections first with the followed-by reason', function () {
    $viewer = Talent::factory()->create();
    $friend = Talent::factory()->create(['slug' => 'the-friend']);
    $friendOfFriend = Talent::factory()->create();
    Talent::factory()->count(3)->create(); // noise

    app(ToggleFollow::class)($viewer, $friend, onlyFollow: true);
    app(ToggleFollow::class)($friend, $friendOfFriend, onlyFollow: true);

    Cache::flush();
    $items = app(SuggestionService::class)->talentsFor($viewer, 10)['items'];

    expect($items[0]['id'])->toBe($friendOfFriend->id)
        ->and($items[0]['reason'])->toBe(__('Followed by @:handle', ['handle' => 'the-friend']));
});

it('gives shared-skill candidates the like-you reason and never fabricates one', function () {
    $skill = TalentType::query()->first() ?? TalentType::factory()->create();
    $viewer = Talent::factory()->create();
    $viewer->talentTypes()->attach($skill->id, ['is_primary' => true, 'position' => 0]);

    $peer = Talent::factory()->create();
    $peer->talentTypes()->attach($skill->id, ['is_primary' => true, 'position' => 0]);

    // Genuinely signal-free: different city, old account, no posts, no skills.
    $stranger = Talent::factory()->create(['base_city' => 'Nowhere-'.uniqid()]);
    $stranger->forceFill(['created_at' => now()->subYears(2)])->saveQuietly();

    Cache::flush();
    $items = collect(app(SuggestionService::class)->talentsFor($viewer, 10)['items']);

    $peerRow = $items->firstWhere('id', $peer->id);
    $strangerRow = $items->firstWhere('id', $stranger->id);

    expect($peerRow['reason'])->toContain(__('like you'))
        ->and($strangerRow['reason'])->toBeNull();
});

it('suggests brands to talents with contract history called out, and none to brands', function () {
    $viewer = Talent::factory()->create();
    $partnerBrand = Brand::factory()->create(['is_published' => true]);
    Brand::factory()->count(2)->create(['is_published' => true]);

    Contract::factory()->create([
        'brand_id' => $partnerBrand->id,
        'talent_id' => $viewer->id,
        'title' => 'SS26 Lookbook',
    ]);

    Cache::flush();
    $items = collect(app(SuggestionService::class)->brandsFor($viewer, 10)['items']);

    expect($items->first()['id'])->toBe($partnerBrand->id)
        ->and($items->first()['reason'])->toBe(__('You worked together on :title', ['title' => 'SS26 Lookbook']));

    // Brands never see brand suggestions.
    $brandViewer = Brand::factory()->create();
    expect(app(SuggestionService::class)->brandsFor($brandViewer, 10)['items'])->toBe([]);
});

it('serves guests a seeded list that is stable for the same session', function () {
    Talent::factory()->count(8)->create();

    $first = $this->getJson('/feed/suggestions/talents?limit=5')->assertOk()->json('data.items');
    $second = $this->getJson('/feed/suggestions/talents?limit=5')->assertOk()->json('data.items');

    expect(collect($first)->pluck('id')->all())->toBe(collect($second)->pluck('id')->all())
        ->and($first)->not->toBeEmpty();
});

// ----- Endpoints + the offset cursor -----------------------------------------

it('pages with the offset cursor so Show more appends', function () {
    $viewer = Talent::factory()->create();
    Talent::factory()->count(8)->create();

    Cache::flush();
    $pageOne = $this->actingAs($viewer, 'talent')
        ->getJson('/feed/suggestions/talents?limit=5')->assertOk()->json('data');

    expect($pageOne['items'])->toHaveCount(5)
        ->and($pageOne['next_offset'])->toBe(5);

    $pageTwo = $this->actingAs($viewer, 'talent')
        ->getJson('/feed/suggestions/talents?limit=5&offset=5')->json('data');

    $all = collect($pageOne['items'])->concat($pageTwo['items'])->pluck('id');
    expect($all->duplicates())->toBeEmpty();
});

it('dismisses permanently through the endpoint, guests gated, rate limited config honoured', function () {
    $viewer = Talent::factory()->create();
    $target = Talent::factory()->create();

    $this->postJson('/feed/suggestions/dismiss', ['type' => 'talent', 'id' => $target->id])
        ->assertStatus(401);

    $this->actingAs($viewer, 'talent')
        ->postJson('/feed/suggestions/dismiss', ['type' => 'talent', 'id' => $target->id])
        ->assertOk();

    Cache::flush();
    $ids = collect(app(SuggestionService::class)->talentsFor($viewer, 50)['items'])->pluck('id');
    expect($ids)->not->toContain($target->id);
});

// ----- Fixed query count ------------------------------------------------------

it('resolves suggestions in a fixed query count regardless of limit', function () {
    $viewer = Talent::factory()->create();
    Talent::factory()->count(12)->create();

    $measure = function (int $limit) use ($viewer): int {
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(SuggestionService::class)->talentsFor($viewer, $limit);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    expect($measure(10))->toBe($measure(3));
});
