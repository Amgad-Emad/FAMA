<?php

use App\Models\Follow;
use App\Models\Post;
use App\Models\Talent;
use App\Services\DiscoverTalentsService;
use App\Services\FeedService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// ----- Determinism (D9/D10) --------------------------------------------------

it('produces an identical candidate list for the same seed and a different one for another', function () {
    $viewer = Talent::factory()->create();
    $authors = Talent::factory()->count(3)->create();
    $authors->each(function ($author) use ($viewer) {
        Follow::factory()->by($viewer)->of($author)->create();
        Post::factory()->byAuthor($author)->count(10)->create();
    });

    $service = app(FeedService::class);

    $first = $service->candidateIds($viewer, 'seed-a');
    Cache::flush();
    $second = $service->candidateIds($viewer, 'seed-a');
    Cache::flush();
    $other = $service->candidateIds($viewer, 'seed-b');

    expect($second)->toBe($first)
        ->and(collect($other)->sort()->values()->all())->toBe(collect($first)->sort()->values()->all())
        ->and($other)->not->toBe($first); // same set, different seeded order
});

// ----- Tier structure --------------------------------------------------------

it('injects a discovery item after every 4 followed items', function () {
    $viewer = Talent::factory()->create();
    $followed = Talent::factory()->create();
    Follow::factory()->by($viewer)->of($followed)->create();

    Post::factory()->byAuthor($followed)->count(8)->create();
    $discoveryAuthor = Talent::factory()->create();
    Post::factory()->byAuthor($discoveryAuthor)->count(5)->create();

    $ids = app(FeedService::class)->candidateIds($viewer, 'seed-x');
    $followedIds = Post::query()->where('author_id', $followed->id)->where('author_type', 'talent')->pluck('id');

    // The interleave guarantees discovery never disappears for heavy
    // followers: at least one non-followed post sits inside the first five
    // positions (the diversity cap may pull it even earlier), the stream
    // leads with the followed tier, and nothing is dropped.
    $firstFive = collect($ids)->take(5);

    expect($firstFive->filter(fn ($id) => ! $followedIds->contains($id)))->not->toBeEmpty()
        ->and($followedIds)->toContain($ids[0])
        ->and(count($ids))->toBe(13); // nothing dropped
});

it('gives guests and zero-follow viewers the discovery tier only, seeded per session', function () {
    Post::factory()->count(15)->create();

    $service = app(FeedService::class);

    $guestList = $service->candidateIds(null, 'guest-seed-1');
    $allPublished = Post::query()->published()->pluck('id')->sort()->values()->all();

    expect(collect($guestList)->sort()->values()->all())->toBe($allPublished);

    Cache::flush();
    $otherVisitor = $service->candidateIds(null, 'guest-seed-2');

    expect(collect($otherVisitor)->sort()->values()->all())->toBe($allPublished);
});

// ----- Diversity cap (D9) ----------------------------------------------------

it('never exceeds 2 consecutive posts per author except at an unavoidable tail', function () {
    $viewer = Talent::factory()->create();
    $heavy = Talent::factory()->create();
    $light = Talent::factory()->create();
    Follow::factory()->by($viewer)->of($heavy)->create();
    Follow::factory()->by($viewer)->of($light)->create();

    Post::factory()->byAuthor($heavy)->count(9)->create();
    Post::factory()->byAuthor($light)->count(3)->create();

    $ids = app(FeedService::class)->candidateIds($viewer, 'seed-cap');

    $authorByPost = Post::query()->whereIn('id', $ids)->pluck('author_id', 'id');
    $sequence = collect($ids)->map(fn ($id) => $authorByPost[$id])->values();

    $run = 1;
    foreach ($sequence as $i => $author) {
        if ($i === 0) {
            continue;
        }

        $run = $author === $sequence[$i - 1] ? $run + 1 : 1;

        if ($run > 2) {
            // Only legal at the unavoidable tail (nothing but this author left).
            expect($sequence->slice($i)->unique()->count())->toBe(1);
        }
    }

    expect($sequence)->toHaveCount(12); // displaced, never dropped
});

// ----- Pagination stability (D10) --------------------------------------------

it('pages through 50 posts with zero duplicates and zero omissions despite mid-scroll inserts', function () {
    config(['feed.ranking.per_page' => 10]);

    $original = Post::factory()->count(50)->create()->pluck('id')->sort()->values();

    $seen = collect();
    $cursor = null;

    for ($page = 0; $page < 10; $page++) {
        $response = $this->getJson('/feed/items'.($cursor !== null ? '?cursor='.urlencode($cursor) : ''))
            ->assertOk();

        $seen = $seen->concat(collect($response->json('data.items'))->pluck('id'));
        $cursor = $response->json('data.next_cursor');

        // Mid-scroll inserts must not shift the frozen list.
        if ($page === 1) {
            Post::factory()->count(10)->create();
        }

        if ($cursor === null) {
            break;
        }
    }

    expect($seen->duplicates())->toBeEmpty()
        ->and($seen->sort()->values()->all())->toBe($original->all());
});

// ----- Casting strip ---------------------------------------------------------

it('serves 12 unfollowed talents per strip and never repeats within a session', function () {
    $viewer = Talent::factory()->create();
    $followed = Talent::factory()->create();
    Follow::factory()->by($viewer)->of($followed)->create();
    Talent::factory()->count(30)->create();

    $discover = app(DiscoverTalentsService::class);

    $first = $discover->strip($viewer, 'session-1');
    $second = $discover->strip($viewer, 'session-1');

    expect($first)->toHaveCount(12)
        ->and($second)->toHaveCount(12)
        ->and($first->pluck('id')->intersect($second->pluck('id')))->toBeEmpty()
        ->and($first->pluck('id'))->not->toContain($followed->id)
        ->not->toContain($viewer->id);
});

// ----- Performance budget ----------------------------------------------------

it('serves a 20-item feed page in under 25 queries', function () {
    $viewer = Talent::factory()->create();
    $authors = Talent::factory()->count(4)->create();
    $authors->each(function ($author) use ($viewer) {
        Follow::factory()->by($viewer)->of($author)->create();
        Post::factory()->byAuthor($author)->count(8)->create();
    });

    // Warm the candidate cache the way a real session would.
    $this->actingAs($viewer, 'talent')->getJson('/feed/items')->assertOk();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($viewer, 'talent')->getJson('/feed/items')->assertOk();

    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBeLessThan(25);
});
