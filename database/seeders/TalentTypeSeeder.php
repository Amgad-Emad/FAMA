<?php

namespace Database\Seeders;

use App\Models\TalentType;
use Illuminate\Database\Seeder;

/**
 * Seeds the six canonical professions. `default_blocks` (ordered block_type keys)
 * decides which blocks a new talent of that type gets pre-loaded. Idempotent on
 * `slug`.
 */
class TalentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'slug' => 'model', 'en' => 'Model', 'ar' => 'عارض/عارضة', 'category' => 'model',
                'default_blocks' => ['hero', 'gallery', 'comp_card', 'look_types', 'digitals', 'brand_collabs', 'reviews', 'services'],
            ],
            [
                'slug' => 'photographer', 'en' => 'Photographer', 'ar' => 'مصور', 'category' => 'crew',
                'default_blocks' => ['hero', 'gallery', 'showreel', 'equipment', 'brand_collabs', 'reviews', 'services'],
            ],
            [
                'slug' => 'cinematographer', 'en' => 'Cinematographer (DOP)', 'ar' => 'مدير تصوير', 'category' => 'crew',
                'default_blocks' => ['hero', 'showreel', 'equipment', 'case_studies', 'reviews', 'services'],
            ],
            [
                'slug' => 'creative-director', 'en' => 'Creative Director', 'ar' => 'مدير إبداعي', 'category' => 'creative',
                'default_blocks' => ['hero', 'gallery', 'case_studies', 'brand_collabs', 'reviews', 'services'],
            ],
            [
                'slug' => 'stylist', 'en' => 'Stylist', 'ar' => 'ستايليست', 'category' => 'creative',
                'default_blocks' => ['hero', 'gallery', 'brand_collabs', 'reviews', 'services'],
            ],
            [
                'slug' => 'graphic-designer', 'en' => 'Graphic Designer', 'ar' => 'مصمم جرافيك', 'category' => 'creative',
                'default_blocks' => ['hero', 'gallery', 'case_studies', 'software_stack', 'reviews', 'services'],
            ],
        ];

        foreach ($types as $type) {
            TalentType::updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => ['en' => $type['en'], 'ar' => $type['ar']],
                    'category' => $type['category'],
                    'default_blocks' => $type['default_blocks'],
                    'icon' => 'lucide-'.$type['slug'],
                    'description' => ['en' => $type['en'].' on Fama.', 'ar' => $type['ar'].' على فاما.'],
                ],
            );
        }
    }
}
