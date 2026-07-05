<?php

namespace Database\Factories;

use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Talent>
 */
class TalentFactory extends Factory
{
    protected $model = Talent::class;

    /**
     * A realistic, published MENA-based talent.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'email_verified_at' => now(),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'is_active' => true,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'display_name' => $name,
            'headline' => [
                'en' => fake()->jobTitle(),
                'ar' => 'مبدع محترف',
            ],
            'bio' => [
                'en' => fake()->paragraph(),
                'ar' => 'نبذة تعريفية عن الموهبة وخبراتها.',
            ],
            'availability_status' => fake()->randomElement(['available', 'booked', 'unavailable']),
            'base_city' => fake()->randomElement(['Cairo', 'Alexandria', 'Giza', 'Dubai', 'Riyadh']),
            'base_country' => fake()->randomElement(['Egypt', 'UAE', 'Saudi Arabia']),
            'rate_tier' => fake()->randomElement(['emerging', 'established', 'premium', 'elite']),
            'willing_to_travel' => fake()->boolean(70),
            'travel_regions' => fake()->randomElements(['MENA', 'GCC', 'Europe', 'Africa'], 2),
            'booking_type' => 'email',
            'booking_value' => fake()->safeEmail(),
            'is_published' => true,
            'published_at' => now(),
            'view_count' => fake()->numberBetween(0, 5000),
            'meta' => null,
        ];
    }

    /**
     * A draft (unpublished) talent still filling out the profile.
     */
    public function draft(): static
    {
        return $this->state(fn (): array => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    /**
     * An available talent (booking CTA open).
     */
    public function available(): static
    {
        return $this->state(fn (): array => ['availability_status' => 'available']);
    }
}
