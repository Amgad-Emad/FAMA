<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'title' => ['en' => fake()->catchPhrase(), 'ar' => 'مشروع'],
            'client_name' => fake()->company(),
            'role' => ['en' => fake()->jobTitle(), 'ar' => 'الدور'],
            'summary' => ['en' => fake()->sentence(), 'ar' => 'ملخص'],
            'body' => ['en' => fake()->paragraphs(3, true), 'ar' => 'المحتوى الكامل'],
            'results' => [
                ['label' => 'Reach', 'value' => (string) fake()->numberBetween(10000, 5000000), 'compare' => ''],
                ['label' => 'ROI', 'value' => fake()->numberBetween(2, 12).'x', 'compare' => ''],
            ],
            // ADR-AA: `occurred_on` is the real field; the mutator derives `year`
            // from it, so setting the date alone keeps both consistent.
            'occurred_on' => fake()->dateTimeBetween('-6 years', 'now')->format('Y-m-d'),
            'url' => fake()->optional()->url(),
            'position' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Mirror a factory-set home skill into the `project_talent_type` pivot so
     * factory-built projects carry the same tag set the Projects editor writes
     * (ADR-X). This runs even inside a WithoutModelEvents seeder, which model
     * hooks would not.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Project $project): void {
            if ($project->talent_type_id !== null && $project->talentTypes()->count() === 0) {
                $project->talentTypes()->attach($project->talent_type_id, ['position' => 0]);
            }
        });
    }
}
