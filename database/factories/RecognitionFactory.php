<?php

namespace Database\Factories;

use App\Models\Recognition;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recognition>
 */
class RecognitionFactory extends Factory
{
    protected $model = Recognition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'author_talent_id' => Talent::factory(),
            'relationship' => $this->faker->randomElement([
                'Shot three campaigns together',
                'Assisted me for two years',
                'Styled every editorial I directed in 2025',
                'We share a studio',
            ]),
            'body' => $this->faker->paragraph(),
            'status' => 'pending',
            'is_approved' => false,
            'recognized_at' => now(),
        ];
    }

    /**
     * Cleared by the admin queue and visible on the public profile.
     */
    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved', 'is_approved' => true]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected', 'is_approved' => false]);
    }
}
