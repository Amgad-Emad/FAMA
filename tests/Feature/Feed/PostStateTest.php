<?php

use App\Models\Comment;
use App\Models\Post;
use App\States\Comment as CommentStates;
use App\States\Post as PostStates;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

// ----- Post lifecycle (feed-spec §5) ----------------------------------------

it('post: draft → published → hidden → published → removed walks the machine', function () {
    $post = Post::factory()->draft()->create();
    expect($post->state->getValue())->toBe('draft');

    $post->state->transitionTo(PostStates\Published::class);
    $post->state->transitionTo(PostStates\Hidden::class);
    $post->state->transitionTo(PostStates\Published::class);
    $post->state->transitionTo(PostStates\Removed::class);

    expect($post->refresh()->state->getValue())->toBe('removed');
});

it('post: draft cannot jump to hidden or removed, and removed is terminal', function () {
    $draft = Post::factory()->draft()->create();

    expect(fn () => $draft->state->transitionTo(PostStates\Hidden::class))
        ->toThrow(CouldNotPerformTransition::class);
    expect(fn () => $draft->state->transitionTo(PostStates\Removed::class))
        ->toThrow(CouldNotPerformTransition::class);

    $removed = Post::factory()->create();
    $removed->state->transitionTo(PostStates\Removed::class);

    expect(fn () => $removed->state->transitionTo(PostStates\Published::class))
        ->toThrow(CouldNotPerformTransition::class);
});

// ----- Comment lifecycle -----------------------------------------------------

it('comment: born published; hidden ⇄ published; removed is terminal', function () {
    $comment = Comment::factory()->create();
    expect($comment->state->getValue())->toBe('published');

    $comment->state->transitionTo(CommentStates\Hidden::class);
    $comment->state->transitionTo(CommentStates\Published::class);
    $comment->state->transitionTo(CommentStates\Removed::class);

    expect($comment->refresh()->state->getValue())->toBe('removed');
    expect(fn () => $comment->state->transitionTo(CommentStates\Published::class))
        ->toThrow(CouldNotPerformTransition::class);
});
