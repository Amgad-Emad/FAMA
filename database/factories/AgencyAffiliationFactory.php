<?php

namespace Database\Factories;

use App\Models\AgencyAffiliation;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgencyAffiliation>
 */
class AgencyAffiliationFactory extends Factory
{
    protected $model = AgencyAffiliation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'agency_name' => fake()->company().' Agency',
            'agency_url' => fake()->optional()->url(),
            'representation_type' => fake()->randomElement(['exclusive', 'non_exclusive', 'mother_agency', 'freelance']),
            'region' => fake()->randomElement(['MENA', 'Europe', 'GCC', 'North America']),
            'is_current' => true,
        ];
    }

    /**
     * A past (ended) representation.
     */
    public function past(): static
    {
        return $this->state(fn (): array => ['is_current' => false]);
    }
}
