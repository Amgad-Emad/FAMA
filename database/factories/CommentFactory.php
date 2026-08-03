<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Talent;
use App\States\Comment\Published;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Default: a published top-level comment by a fresh talent.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'parent_id' => null,
            'author_type' => 'talent',
            'author_id' => Talent::factory(),
            'body' => fake()->sentence(),
            'state' => Published::$name,
        ];
    }

    /**
     * Attribute the comment to an existing actor (talent or brand).
     */
    public function byAuthor(Talent|Brand|Model $author): static
    {
        return $this->state(fn (): array => [
            'author_type' => $author->getMorphClass(),
            'author_id' => $author->getKey(),
        ]);
    }

    /**
     * A reply to the given comment (depth two — ADR-F7).
     */
    public function replyTo(Comment $parent): static
    {
        return $this->state(fn (): array => [
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
        ]);
    }
}
