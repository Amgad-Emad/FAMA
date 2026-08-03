<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Project;
use App\Models\Talent;
use App\States\Post\Draft;
use App\States\Post\Published;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Default: a published text note by a fresh talent.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_type' => 'talent',
            'author_id' => Talent::factory(),
            'post_type' => 'note',
            'body' => fake()->paragraph(),
            'state' => Published::$name,
            'published_at' => now(),
        ];
    }

    /**
     * Attribute the post to an existing author (talent — or brand, reserved).
     */
    public function byAuthor(Talent $talent): static
    {
        return $this->state(fn (): array => [
            'author_type' => $talent->getMorphClass(),
            'author_id' => $talent->getKey(),
        ]);
    }

    /**
     * An unpublished draft.
     */
    public function draft(): static
    {
        return $this->state(fn (): array => [
            'state' => Draft::$name,
            'published_at' => null,
        ]);
    }

    /**
     * A media post (the media items themselves attach via the media library).
     */
    public function mediaPost(): static
    {
        return $this->state(fn (): array => [
            'post_type' => 'media',
            'body' => fake()->boolean(70) ? fake()->sentence() : null,
        ]);
    }

    /**
     * A project_share referencing the given (or a fresh) Project, with the
     * frozen snapshot fallback (ADR-F2).
     */
    public function sharingProject(?Project $project = null): static
    {
        return $this->state(function (array $attributes) use ($project): array {
            $project ??= Project::factory()->create([
                'talent_id' => $attributes['author_id'] instanceof \Illuminate\Database\Eloquent\Factories\Factory
                    ? Talent::factory()
                    : $attributes['author_id'],
            ]);

            return [
                'post_type' => 'project_share',
                'author_type' => 'talent',
                'author_id' => $project->talent_id,
                'project_id' => $project->id,
                'shared_snapshot' => [
                    'title' => $project->title,
                    'cover_media_path' => null,
                    'shared_at' => now()->toIso8601String(),
                ],
            ];
        });
    }

    /**
     * Published at a specific moment (ranking/window tests).
     */
    public function publishedAt(\DateTimeInterface $when): static
    {
        return $this->state(fn (): array => [
            'state' => Published::$name,
            'published_at' => $when,
        ]);
    }
}
