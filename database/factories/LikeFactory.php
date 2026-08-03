<?php

namespace Database\Factories;

use App\Models\Like;
use App\Models\Post;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Like>
 */
class LikeFactory extends Factory
{
    protected $model = Like::class;

    /**
     * Default: a fresh talent liking a fresh post.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'likeable_type' => 'post',
            'likeable_id' => Post::factory(),
            'liker_type' => 'talent',
            'liker_id' => Talent::factory(),
        ];
    }

    /**
     * Target an existing likeable (post or comment).
     */
    public function on(Model $likeable): static
    {
        return $this->state(fn (): array => [
            'likeable_type' => $likeable->getMorphClass(),
            'likeable_id' => $likeable->getKey(),
        ]);
    }

    /**
     * Attribute the like to an existing actor (talent or brand).
     */
    public function by(Model $liker): static
    {
        return $this->state(fn (): array => [
            'liker_type' => $liker->getMorphClass(),
            'liker_id' => $liker->getKey(),
        ]);
    }
}
