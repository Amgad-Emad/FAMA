<?php

namespace Database\Factories;

use App\Models\PortfolioItem;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortfolioItem>
 */
class PortfolioItemFactory extends Factory
{
    protected $model = PortfolioItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'block_id' => null,
            'media_type' => 'image',
            'embed_url' => null,
            'caption' => ['en' => fake()->sentence(3), 'ar' => 'تعليق'],
            'credits' => ['photographer' => fake()->name()],
            'tags' => fake()->randomElements(['editorial', 'studio', 'outdoor', 'bw'], 2),
            'position' => fake()->numberBetween(0, 30),
        ];
    }

    /**
     * An external embed (e.g. a YouTube/Vimeo link) rather than an upload.
     */
    public function embed(): static
    {
        return $this->state(fn (): array => [
            'media_type' => 'embed',
            'embed_url' => 'https://www.youtube.com/watch?v='.fake()->lexify('???????????'),
        ]);
    }
}
