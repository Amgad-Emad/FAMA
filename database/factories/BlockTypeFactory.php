<?php

namespace Database\Factories;

use App\Models\BlockType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BlockType>
 */
class BlockTypeFactory extends Factory
{
    protected $model = BlockType::class;

    /**
     * A catalog block, universal by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->unique()->slug(2);

        return [
            'key' => $key,
            'name' => ['en' => Str::headline($key), 'ar' => 'كتلة'],
            'description' => ['en' => fake()->sentence(), 'ar' => 'وصف الكتلة'],
            'icon' => 'lucide-square',
            'availability' => 'universal',
            'content_source' => fake()->randomElement(['inline', 'table']),
            'default_layout' => fake()->randomElement(['grid', 'carousel', 'list', 'masonry']),
            'is_active' => true,
            'is_repeatable' => fake()->boolean(30),
            'position' => fake()->numberBetween(0, 20),
            'settings_schema' => null,
        ];
    }

    /**
     * Gated to specific categories.
     */
    public function byCategory(): static
    {
        return $this->state(fn (): array => ['availability' => 'by_category']);
    }

    /**
     * Gated to specific professions.
     */
    public function byType(): static
    {
        return $this->state(fn (): array => ['availability' => 'by_type']);
    }
}
