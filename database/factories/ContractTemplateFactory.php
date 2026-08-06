<?php

namespace Database\Factories;

use App\Models\ContractTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractTemplate>
 */
class ContractTemplateFactory extends Factory
{
    protected $model = ContractTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' contract';

        return [
            'name' => ucfirst($name),
            'description' => fake()->optional()->sentence(),
            'applies_to' => null,
            'is_active' => true,
            'is_default' => false,
            'deposit_pct' => 50,
            'deposit_holder' => 'Fama',
            'cancellation_forfeit_pct' => 50,
            'revision_rounds' => 1,
            'preview_currency' => 'EGP',
            'intro' => 'Between {brand} and {talent}',
            'clauses' => ContractTemplate::defaultClauses(),
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => ['is_default' => true, 'is_active' => true]);
    }
}
