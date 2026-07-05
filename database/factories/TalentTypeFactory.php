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
     * A profession lookup row.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $profession = fake()->randomElement([
            'Model', 'Photographer', 'Cinematographer', 'Creative Director', 'Stylist', 'Graphic Designer',
        ]);

        return [
            'name' => ['en' => $profession, 'ar' => $profession],
            'slug' => Str::slug($profession).'-'.fake()->unique()->numerify('###'),
            'category' => fake()->randomElement(['model', 'crew', 'creative']),
            'default_blocks' => ['hero', 'gallery'],
            'icon' => 'lucide-'.Str::slug($profession),
            'description' => ['en' => fake()->sentence(), 'ar' => 'وصف المهنة'],
        ];
    }
}
