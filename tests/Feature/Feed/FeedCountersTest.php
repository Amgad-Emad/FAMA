<?php

use App\Models\Brand;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\Talent;
use App\States\Post\Hidden;
use Illuminate\Support\Facades\DB;

// ----- Observer-maintained counters (ADR-F6) --------------------------------

it('maintains likes_count on posts and comments through like create/delete', function () {
    $post = Post::factory()->create();
    $comment = Comment::factory()->create(['post_id' => $post->id]);

    $postLike = Like::factory()->on($post)->create();
    $commentLike = Like::factory()->on($comment)->create();

    expect($post->refresh()->likes_count)->toBe(1)
        ->and($comment->refresh()->likes_count)->toBe(1);

    $postLike->delete();
    $commentLike->delete();

    expect($post->refresh()->likes_count)->toBe(0)
        ->and($comment->refresh()->likes_count)->toBe(0);
});

it('maintains comments_count and replies_count, tombstoning included', function () {
    $post = Post::factory()->create();
    $comment = Comment::factory()->create(['post_id' => $post->id]);
    $reply = Comment::factory()->replyTo($comment)->create();

    expect($post->refresh()->comments_count)->toBe(2)
        ->and($comment->refresh()->replies_count)->toBe(1);

    $reply->delete(); // soft delete → tombstone, no longer counted

    expect($post->refresh()->comments_count)->toBe(1)
        ->and($comment->refresh()->replies_count)->toBe(0);

    $reply->restore();

    expect($post->refresh()->comments_count)->toBe(2)
        ->and($comment->refresh()->replies_count)->toBe(1);
});

it('maintains follower/following counters for talents and brands', function () {
    $talent = Talent::factory()->create();
    $fan = Talent::factory()->create();
    $brand = Brand::factory()->create();

    $edgeA = Follow::factory()->by($fan)->of($talent)->create();
    Follow::factory()->by($brand)->of($talent)->create();

    expect($talent->refresh()->followers_count)->toBe(2)
        ->and($fan->refresh()->following_count)->toBe(1)
        ->and($brand->refresh()->following_count)->toBe(1);

    $edgeA->delete();

    expect($talent->refresh()->followers_count)->toBe(1)
        ->and($fan->refresh()->following_count)->toBe(0);
});

it('counts posts_count only while a post is published and alive', function () {
    $talent = Talent::factory()->create();

    $draft = Post::factory()->byAuthor($talent)->draft()->create();
    expect($talent->refresh()->posts_count)->toBe(0);

    $draft->state->transitionTo(App\States\Post\Published::class);
    expect($talent->refresh()->posts_count)->toBe(1);

    $draft->refresh()->state->transitionTo(Hidden::class);
    expect($talent->refresh()->posts_count)->toBe(0);

    $draft->refresh()->state->transitionTo(App\States\Post\Published::class);
    $draft->refresh()->delete();
    expect($talent->refresh()->posts_count)->toBe(0);

    $draft->restore();
    expect($talent->refresh()->posts_count)->toBe(1);
});

// ----- Nightly reconciliation (ADR-F6) ---------------------------------------

it('feed:reconcile-counters repairs corrupted counters from source', function () {
    $talent = Talent::factory()->create();
    $post = Post::factory()->byAuthor($talent)->create();
    $comment = Comment::factory()->create(['post_id' => $post->id]);
    Comment::factory()->replyTo($comment)->count(2)->create();
    Like::factory()->on($post)->count(3)->create();
    Follow::factory()->of($talent)->count(2)->create();

    // Corrupt every counter behind the observers' backs.
    DB::table('posts')->update(['likes_count' => 99, 'comments_count' => 99]);
    DB::table('comments')->update(['likes_count' => 99, 'replies_count' => 99]);
    DB::table('talents')->where('id', $talent->id)
        ->update(['followers_count' => 99, 'following_count' => 99, 'posts_count' => 99]);

    $this->artisan('feed:reconcile-counters')
        ->expectsOutputToContain('repaired')
        ->assertSuccessful();

    expect($post->refresh()->likes_count)->toBe(3)
        ->and($post->comments_count)->toBe(3)
        ->and($comment->refresh()->replies_count)->toBe(2)
        ->and($talent->refresh()->followers_count)->toBe(2)
        ->and($talent->following_count)->toBe(0)
        ->and($talent->posts_count)->toBe(1);
});

it('feed:reconcile-counters reports a clean pass when nothing drifted', function () {
    Post::factory()->create();

    $this->artisan('feed:reconcile-counters')
        ->expectsOutputToContain('All feed counters were consistent.')
        ->assertSuccessful();
});
