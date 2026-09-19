<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectCredit;
use App\Models\Talent;
use App\Models\TalentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectCredit>
 */
class ProjectCreditFactory extends Factory
{
    protected $model = ProjectCredit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'talent_id' => Talent::factory(),
            'name' => null,
            'talent_type_id' => TalentType::factory(),
            'role' => $this->faker->randomElement(['Photographer', 'Stylist', 'First AD', 'Groomer', 'Retoucher']),
            'status' => 'pending',
            'position' => 0,
        ];
    }

    /** Confirmed by the collaborator — public on both profiles. */
    public function accepted(): static
    {
        return $this->state(fn () => ['status' => 'accepted', 'responded_at' => now()]);
    }

    /** Someone not on Fama: display-only, never pending. */
    public function offPlatform(): static
    {
        return $this->state(fn () => [
            'talent_id' => null,
            'name' => $this->faker->name(),
            'status' => 'listed',
        ]);
    }
}
