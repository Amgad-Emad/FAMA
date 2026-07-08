<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignMedia>
 */
class CampaignMediaFactory extends Factory
{
    protected $model = CampaignMedia::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'media_type' => 'image',
            'embed_url' => null,
            'caption' => ['en' => fake()->words(2, true), 'ar' => 'تعليق'],
            'position' => 0,
        ];
    }
}
