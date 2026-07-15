<?php

namespace Database\Factories;

use App\Models\ContractEnquiry;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractEnquiry>
 */
class ContractEnquiryFactory extends Factory
{
    protected $model = ContractEnquiry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_company' => fake()->company(),
            'brief' => fake()->paragraph(),
            'status' => 'new',
            'converted_contract_id' => null,
        ];
    }
}
