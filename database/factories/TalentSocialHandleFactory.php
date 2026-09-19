<?php

namespace Database\Factories;

use App\Models\Talent;
use App\Models\TalentSocialHandle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TalentSocialHandle>
 */
class TalentSocialHandleFactory extends Factory
{
    protected $model = TalentSocialHandle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // (talent_id, platform) is unique, so the platform must not repeat when
        // several handles are made for one talent in the same test.
        $platform = $this->faker->unique()->randomElement([
            'instagram', 'tiktok', 'x', 'linkedin', 'youtube', 'behance', 'vimeo', 'website', 'other',
        ]);
        $handle = $this->faker->userName();

        return [
            'talent_id' => Talent::factory(),
            'platform' => $platform,
            'handle' => $handle,
            'url' => "https://{$platform}.com/{$handle}",
            'position' => 0,
        ];
    }
}
