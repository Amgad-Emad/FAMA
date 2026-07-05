<?php

namespace Database\Factories;

use App\Models\BlockType;
use App\Models\BlockTypeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockTypeCategory>
 */
class BlockTypeCategoryFactory extends Factory
{
    protected $model = BlockTypeCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'block_type_id' => BlockType::factory(),
            'category' => fake()->randomElement(['model', 'crew', 'creative']),
        ];
    }
}
