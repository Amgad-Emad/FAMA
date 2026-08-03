<?php

use App\Models\Brand;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Talent;
use Illuminate\Support\Facades\DB;

// ----- Listing (feed-spec §3) ------------------------------------------------

it('lists top-level comments newest-first with 3 preview replies and a remaining count', function () {
    $post = Post::factory()->create();
    $comments = Comment::factory()->count(12)->create(['post_id' => $post->id])->each(
        fn ($comment, $i) => $comment->forceFill(['created_at' => now()->subMinutes(100 - $i)])->save()
    );
    $threaded = $comments->last();
    Comment::factory()->count(5)->replyTo($threaded)->create();

    $response = $this->getJson("/posts/{$post->id}/comments")->assertOk();

    $rows = collect($response->json('data'));

    expect($rows)->toHaveCount(10)
        ->and($rows->first()['id'])->toBe($threaded->id) // newest first
        ->and($rows->first()['replies'])->toHaveCount(3)
        ->and($rows->first()['remaining_replies'])->toBe(2)
        ->and($response->json('meta.pagination.total'))->toBe(12);
});

it('keeps a deleted comment with replies as a tombstone and drops one without', function () {
    $post = Post::factory()->create();
    $withReplies = Comment::factory()->create(['post_id' => $post->id]);
    Comment::factory()->replyTo($withReplies)->create();
    $childless = Comment::factory()->create(['post_id' => $post->id]);

    $withReplies->delete();
    $childless->delete();

    $rows = collect($this->getJson("/posts/{$post->id}/comments")->json('data'));

    expect($rows->pluck('id'))->toContain($withReplies->id)
        ->not->toContain($childless->id)
        ->and($rows->firstWhere('id', $withReplies->id)['is_tombstone'])->toBeTrue()
        ->and($rows->firstWhere('id', $withReplies->id)['body_html'])->toBeNull();
});

it('paginates replies separately, oldest first', function () {
    $comment = Comment::factory()->create();
    $replies = Comment::factory()->count(12)->replyTo($comment)->create()->each(
        fn ($reply, $i) => $reply->forceFill(['created_at' => now()->subMinutes(100 - $i)])->save()
    );

    $response = $this->getJson("/comments/{$comment->id}/replies")->assertOk();

    expect(collect($response->json('data'))->first()['id'])->toBe($replies->first()->id)
        ->and($response->json('meta.pagination.total'))->toBe(12);
});

it('resolves the viewer like state in bulk, no per-row queries', function () {
    $talent = Talent::factory()->create();
    $post = Post::factory()->create();
    $liked = Comment::factory()->create(['post_id' => $post->id]);
    Comment::factory()->create(['post_id' => $post->id]);
    Like::factory()->on($liked)->by($talent)->create();

    $rows = collect($this->actingAs($talent, 'talent')
        ->getJson("/posts/{$post->id}/comments")
        ->json('data'));

    expect($rows->firstWhere('id', $liked->id)['liked_by_viewer'])->toBeTrue()
        ->and($rows->firstWhere('id', '!=', $liked->id)['liked_by_viewer'])->toBeFalse();
});

it('executes a fixed number of queries regardless of thread size', function () {
    // Same shape (threads with replies), different sizes — the query count
    // must not scale with rows.
    $small = Post::factory()->create();
    Comment::factory()->count(2)->create(['post_id' => $small->id])
        ->each(fn ($c) => Comment::factory()->count(1)->replyTo($c)->create());

    $large = Post::factory()->create();
    Comment::factory()->count(10)->create(['post_id' => $large->id])
        ->each(fn ($c) => Comment::factory()->count(3)->replyTo($c)->create());

    $count = function (Post $post): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson("/posts/{$post->id}/comments")->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    expect($count($large))->toBe($count($small));
});

// ----- Comment likes ---------------------------------------------------------

it('likes and unlikes a comment with counters moving', function () {
    $talent = Talent::factory()->create();
    $comment = Comment::factory()->create();

    $this->actingAs($talent, 'talent')
        ->postJson("/comments/{$comment->id}/like")
        ->assertOk()
        ->assertJsonPath('data.likes_count', 1);

    $this->actingAs($talent, 'talent')
        ->deleteJson("/comments/{$comment->id}/like")
        ->assertJsonPath('data.likes_count', 0);
});

// ----- Edits -----------------------------------------------------------------

it('lets an author edit their comment inside the window and locks it after', function () {
    $talent = Talent::factory()->create();
    Talent::factory()->create(['slug' => 'rana']);

    $fresh = Comment::factory()->byAuthor($talent)->create();

    $this->actingAs($talent, 'talent')
        ->patchJson("/comments/{$fresh->id}", ['body' => 'Edited — cc @rana'])
        ->assertOk();

    expect($fresh->refresh()->edited_at)->not->toBeNull()
        ->and($fresh->mentions()->count())->toBe(1);

    $stale = Comment::factory()->byAuthor($talent)->create(['created_at' => now()->subMinutes(20)]);

    $this->actingAs($talent, 'talent')
        ->patchJson("/comments/{$stale->id}", ['body' => 'Too late'])
        ->assertStatus(403);
});

it('forbids a brand from commenting (brands like and follow talents, but never comment)', function () {
    $brand = Brand::factory()->create();
    $post = \App\Models\Post::factory()->create();

    $this->actingAs($brand, 'brand')
        ->postJson("/posts/{$post->id}/comments", ['body' => 'A brand comment'])
        ->assertStatus(403);
});

// ----- Rate limits -----------------------------------------------------------

it('caps comments at 10 per minute with a friendly 429 envelope', function () {
    $talent = Talent::factory()->create();
    $post = Post::factory()->create();

    for ($i = 1; $i <= 10; $i++) {
        $this->actingAs($talent, 'talent')
            ->postJson("/posts/{$post->id}/comments", ['body' => "Comment {$i}"])
            ->assertCreated();
    }

    $this->actingAs($talent, 'talent')
        ->postJson("/posts/{$post->id}/comments", ['body' => 'One too many'])
        ->assertStatus(429)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', __('Slow down a little — please try again in a moment.'));
});
