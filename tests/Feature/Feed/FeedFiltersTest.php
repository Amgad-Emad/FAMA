<?php

use App\Models\Follow;
use App\Models\Post;
use App\Models\Talent;
use App\Models\TalentType;

// ----- Filtered feed modes (5P) ----------------------------------------------

it('restricts the stream to one skill and keeps the filter inside the cursor', function () {
    config(['feed.ranking.per_page' => 2]);

    $skill = TalentType::factory()->create(['slug' => 'photography-x', 'name' => ['en' => 'Photography X']]);
    $tagged = Post::factory()->count(5)->create();
    $tagged->each(fn ($post) => $post->skills()->attach($skill->id));
    Post::factory()->count(4)->create(); // untagged noise

    $first = $this->getJson('/feed/items?skill=photography-x')->assertOk();

    expect($first->json('data.total'))->toBe(5);

    // The cursor carries the filter — page 2 needs no query params.
    $second = $this->getJson('/feed/items?cursor='.urlencode($first->json('data.next_cursor')))->assertOk();

    $ids = collect($first->json('data.items'))->pluck('id')
        ->concat(collect($second->json('data.items'))->pluck('id'));

    expect($ids->unique())->toHaveCount(4)
        ->and(Post::query()->findMany($ids)->every(
            fn ($post) => $post->skills()->where('slug', 'photography-x')->exists()
        ))->toBeTrue();
});

it('restricts ?following=1 to followed authors only', function () {
    $viewer = Talent::factory()->create();
    $followed = Talent::factory()->create();
    Follow::factory()->by($viewer)->of($followed)->create();

    Post::factory()->byAuthor($followed)->count(3)->create();
    Post::factory()->count(4)->create(); // discovery noise

    $response = $this->actingAs($viewer, 'talent')
        ->getJson('/feed/items?following=1')
        ->assertOk();

    expect($response->json('data.total'))->toBe(3)
        ->and(collect($response->json('data.items'))->pluck('author.id')->unique()->all())
        ->toBe([$followed->id]);
});

it('renders the filter pills and accepts the filtered page load', function () {
    $this->get('/feed?skill=modeling')->assertOk();
    $this->get('/feed?following=1')->assertOk();
});
