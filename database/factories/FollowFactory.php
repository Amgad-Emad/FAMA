<?php

namespace Database\Factories;

use App\Models\Follow;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Follow>
 */
class FollowFactory extends Factory
{
    protected $model = Follow::class;

    /**
     * Default: a fresh talent following another fresh talent.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'follower_type' => 'talent',
            'follower_id' => Talent::factory(),
            'followable_type' => 'talent',
            'followable_id' => Talent::factory(),
            'created_at' => now(),
        ];
    }

    /**
     * Attribute the edge to an existing follower (talent or brand).
     */
    public function by(Model $follower): static
    {
        return $this->state(fn (): array => [
            'follower_type' => $follower->getMorphClass(),
            'follower_id' => $follower->getKey(),
        ]);
    }

    /**
     * Point the edge at an existing followable (talent).
     */
    public function of(Model $followable): static
    {
        return $this->state(fn (): array => [
            'followable_type' => $followable->getMorphClass(),
            'followable_id' => $followable->getKey(),
        ]);
    }
}
