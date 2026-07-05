<?php

namespace Database\Factories;

use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfileBlock>
 */
class ProfileBlockFactory extends Factory
{
    protected $model = ProfileBlock::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'block_type_id' => BlockType::factory(),
            'title' => ['en' => fake()->words(2, true), 'ar' => 'قسم'],
            'position' => fake()->numberBetween(0, 20),
            'is_visible' => true,
            'layout' => fake()->randomElement(['grid', 'carousel', 'list', 'masonry']),
            'settings' => [],
            'content' => null,
        ];
    }

    /**
     * A hidden block.
     */
    public function hidden(): static
    {
        return $this->state(fn (): array => ['is_visible' => false]);
    }
}
