<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Contract;
use App\Models\ContractFlow;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'FAMA-2026-'.fake()->unique()->numerify('#####'),
            'brand_id' => Brand::factory(),
            'talent_id' => Talent::factory(),
            'contract_flow_id' => ContractFlow::factory(),
            'current_step_id' => null,
            'status' => 'draft',
            'title' => ucfirst(fake()->words(3, true)),
            'brief' => fake()->paragraph(),
            'agreed_amount' => null,
            'currency' => 'EGP',
            'initiated_by' => 'brand',
        ];
    }
}
