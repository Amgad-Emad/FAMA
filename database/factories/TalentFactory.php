<?php

namespace Database\Factories;

use App\Models\Speciality;
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
            // A real city/country pair (both are drop lists in the editor).
            ...(fn (array $place): array => ['base_city' => $place[0], 'base_country' => $place[1]])(fake()->randomElement([['Cairo', 'Egypt'], ['Alexandria', 'Egypt'], ['Giza', 'Egypt'], ['Dubai', 'United Arab Emirates'], ['Riyadh', 'Saudi Arabia']])),
            'booking_type' => 'email',
            'booking_value' => fake()->safeEmail(),
            'is_verified' => true,
            'is_published' => true,
            // Explicit so a factory model matches a real row: relying on the
            // column default leaves the attribute unhydrated (ADR-Z).
            'is_available' => true,
            'will_travel' => false,
            'published_at' => now(),
            'status' => 'live',
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
            'status' => 'draft',
        ]);
    }

    /**
     * Not yet checked by an admin. Since ADR-AC this is only a badge — it no
     * longer stops a profile going Live.
     */
    public function unverified(): static
    {
        return $this->state(fn (): array => ['is_verified' => false]);
    }

    /**
     * Every Go Live step saved (ADR-AC): a display name, a city (from the
     * definition) and one skill with one speciality attached.
     */
    public function goLiveReady(): static
    {
        return $this->afterCreating(function (Talent $talent): void {
            if (blank($talent->display_name)) {
                $talent->update(['display_name' => fake()->name()]);
            }
            $speciality = Speciality::factory()->create();
            $talent->talentTypes()->attach($speciality->talent_type_id, ['is_primary' => true, 'position' => 0]);
            $talent->specialities()->attach($speciality->id, ['position' => 0]);
        });
    }
}
