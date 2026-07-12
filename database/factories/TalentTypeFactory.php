<?php

namespace Database\Factories;

use App\Models\TalentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TalentType>
 */
class TalentTypeFactory extends Factory
{
    protected $model = TalentType::class;

    /**
     * A skill catalog row (`talent_types`).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $skill = fake()->randomElement([
            'Modeling', 'Photography', 'Cinematography', 'Creative Direction', 'Styling', 'Graphic Design',
        ]);

        return [
            'name' => ['en' => $skill, 'ar' => $skill],
            'slug' => Str::slug($skill).'-'.fake()->unique()->numerify('###'),
            'category' => fake()->randomElement(['model', 'crew', 'creative']),
            'default_blocks' => ['hero', 'gallery'],
            'icon' => 'lucide-'.Str::slug($skill),
            'description' => ['en' => fake()->sentence(), 'ar' => 'وصف المهارة'],
        ];
    }
}
