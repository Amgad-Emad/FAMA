<?php

namespace Database\Factories;

use App\Models\DealEnquiry;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DealEnquiry>
 */
class DealEnquiryFactory extends Factory
{
    protected $model = DealEnquiry::class;

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
            'converted_deal_id' => null,
        ];
    }
}
