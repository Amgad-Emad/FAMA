<?php

use App\Actions\Feed\ToggleBookmark;
use App\Models\Bookmark;
use App\Models\Brand;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Talent;
use App\Models\TalentType;
use Illuminate\Support\Facades\DB;

// ----- 5X: toggle + counters + type gate -------------------------------------

it('saves and unsaves idempotently with the counter moving in-transaction', function () {
    $talent = Talent::factory()->create();
    $post = Post::factory()->create();

    $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/bookmark")
        ->assertOk()
        ->assertJsonPath('data.saved', true)
        ->assertJsonPath('data.bookmarks_count', 1);

    // Double submit — still one row, one count.
    $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/bookmark")
        ->assertJsonPath('data.bookmarks_count', 1);

    expect(Bookmark::query()->count())->toBe(1);

    $this->actingAs($talent, 'talent')
        ->deleteJson("/posts/{$post->id}/bookmark")
        ->assertJsonPath('data.saved', false)
        ->assertJsonPath('data.bookmarks_count', 0);
});

it('rejects saving anything but a post with a clear message', function () {
    $talent = Talent::factory()->create();
    $comment = Comment::factory()->create();

    expect(fn () => app(ToggleBookmark::class)($talent, $comment))
        ->toThrow(InvalidArgumentException::class, __('Only posts can be saved for now.'));
});

it('gates guests with the intent envelope and replays the save after login', function () {
    $talent = Talent::factory()->create(['password' => bcrypt('password')]);
    $post = Post::factory()->create();

    $this->postJson("/posts/{$post->id}/bookmark")
        ->assertStatus(401)
        ->assertJsonPath('data.intent.action', 'bookmark');

    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent']);

    expect(Bookmark::query()->where([
        'bookmarker_type' => 'talent', 'bookmarker_id' => $talent->id,
        'bookmarkable_type' => 'post', 'bookmarkable_id' => $post->id,
    ])->exists())->toBeTrue();
});

it('covers bookmarks_count in the nightly reconcile', function () {
    $post = Post::factory()->create();
    Talent::factory()->count(2)->create()->each(
        fn ($talent) => app(ToggleBookmark::class)($talent, $post, onlySave: true)
    );

    DB::table('posts')->update(['bookmarks_count' => 99]);

    $this->artisan('feed:reconcile-counters')->assertSuccessful();

    expect($post->refresh()->bookmarks_count)->toBe(2);
});

// ----- The dashboard collections (ADR-F9) ------------------------------------

it('serves the talent SAVED and LIKED collections, scoped and filterable', function () {
    $talent = Talent::factory()->create();
    $other = Talent::factory()->create();
    $skill = TalentType::query()->first() ?? TalentType::factory()->create();

    $savedTagged = Post::factory()->create(['body' => 'Golden hour set']);
    $savedTagged->skills()->attach($skill->id);
    $savedPlain = Post::factory()->create(['body' => 'Another day']);
    $liked = Post::factory()->create(['body' => 'Liked one']);
    $othersSave = Post::factory()->create();

    app(ToggleBookmark::class)($talent, $savedTagged, onlySave: true);
    app(ToggleBookmark::class)($talent, $savedPlain, onlySave: true);
    Like::factory()->on($liked)->by($talent)->create();
    app(ToggleBookmark::class)($other, $othersSave, onlySave: true);

    // Pages render (the old routes now redirect into the consolidated Activity tabs).
    $this->actingAs($talent, 'talent')->get('/talent/feed/saved')
        ->assertRedirect(route('talent.feed.activity', ['view' => 'saved']));
    $this->actingAs($talent, 'talent')->get('/talent/feed/activity?view=saved')->assertOk();
    $this->actingAs($talent, 'talent')->get('/talent/feed/activity?view=liked')->assertOk();

    // Saved: only the talent's own saves, never another actor's (ADR-F9).
    $saved = collect($this->actingAs($talent, 'talent')
        ->getJson('/talent/feed/collection/data?mode=saved')
        ->assertOk()->json('data'));

    expect($saved->pluck('id'))->toContain($savedTagged->id)
        ->toContain($savedPlain->id)
        ->not->toContain($othersSave->id);

    // Skill filter + body search narrow server-side.
    $filtered = collect($this->actingAs($talent, 'talent')
        ->getJson('/talent/feed/collection/data?mode=saved&skill='.$skill->slug)
        ->json('data'));
    expect($filtered->pluck('id'))->toContain($savedTagged->id)->not->toContain($savedPlain->id);

    $searched = collect($this->actingAs($talent, 'talent')
        ->getJson('/talent/feed/collection/data?mode=saved&q=Golden')
        ->json('data'));
    expect($searched->pluck('id')->all())->toBe([$savedTagged->id]);

    // Liked mode.
    $likedRows = collect($this->actingAs($talent, 'talent')
        ->getJson('/talent/feed/collection/data?mode=liked')
        ->json('data'));
    expect($likedRows->pluck('id')->all())->toBe([$liked->id]);
});

it('serves the brand SAVED collection and keeps guests out', function () {
    $brand = Brand::factory()->create();
    $post = Post::factory()->create();
    app(ToggleBookmark::class)($brand, $post, onlySave: true);

    $this->actingAs($brand, 'brand')->get('/brand/feed/saved')->assertOk();

    $rows = collect($this->actingAs($brand, 'brand')
        ->getJson('/brand/feed/saved/data')
        ->assertOk()->json('data'));

    expect($rows->pluck('id')->all())->toBe([$post->id]);
});

it('keeps guests out of the collections', function () {
    // Separate test — a session can hold several guards at once.
    $this->getJson('/brand/feed/saved/data')->assertStatus(401);
    $this->get('/talent/feed/saved')->assertRedirect();
});

it('answers the collection within a fixed query count regardless of size', function () {
    $talent = Talent::factory()->create();

    $save = function (int $count) use ($talent): void {
        Post::factory()->count($count)->create()
            ->each(fn ($post) => app(ToggleBookmark::class)($talent, $post, onlySave: true));
    };

    $measure = function () use ($talent): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($talent, 'talent')->getJson('/talent/feed/collection/data?mode=saved')->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    $save(2);
    $small = $measure();

    $save(8);
    $large = $measure();

    expect($large)->toBe($small);
});
