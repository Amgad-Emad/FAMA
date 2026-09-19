<?php

namespace Database\Factories;

use App\Models\Talent;
use App\Models\TalentInterest;
use App\Models\TalentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TalentInterest>
 */
class TalentInterestFactory extends Factory
{
    protected $model = TalentInterest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'talent_id' => Talent::factory(),
            'talent_type_id' => TalentType::factory(),
            'speciality_id' => null,
            'note' => null,
            'position' => 0,
        ];
    }
}
