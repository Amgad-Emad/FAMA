<?php

namespace Database\Seeders;

use App\Models\TalentType;
use Illuminate\Database\Seeder;

/**
 * Seeds the six canonical skills (the `talent_types` catalog — ADR-N). Skills are
 * named as the DISCIPLINE/ACTIVITY, not the person (ADR-S): "Modeling", not "Model".
 * `default_blocks` (ordered block_type keys) decides which blocks a new talent of
 * that skill gets pre-loaded. `category` stays the DB enum (model/crew/creative).
 * Idempotent on `slug`.
 */
class TalentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'slug' => 'modeling', 'en' => 'Modeling', 'ar' => 'عرض الأزياء', 'category' => 'model',
                'default_blocks' => ['hero', 'gallery', 'comp_card', 'look_types', 'digitals', 'brand_collabs', 'reviews'],
            ],
            [
                'slug' => 'photography', 'en' => 'Photography', 'ar' => 'التصوير الفوتوغرافي', 'category' => 'crew',
                'default_blocks' => ['hero', 'gallery', 'showreel', 'equipment', 'brand_collabs', 'reviews'],
            ],
            [
                'slug' => 'cinematography', 'en' => 'Cinematography', 'ar' => 'التصوير السينمائي', 'category' => 'crew',
                'default_blocks' => ['hero', 'showreel', 'equipment', 'projects', 'reviews'],
            ],
            [
                'slug' => 'creative-direction', 'en' => 'Creative Direction', 'ar' => 'الإدارة الإبداعية', 'category' => 'creative',
                'default_blocks' => ['hero', 'gallery', 'projects', 'brand_collabs', 'reviews'],
            ],
            [
                'slug' => 'styling', 'en' => 'Styling', 'ar' => 'تنسيق الأزياء', 'category' => 'creative',
                'default_blocks' => ['hero', 'gallery', 'brand_collabs', 'reviews'],
            ],
            [
                'slug' => 'graphic-design', 'en' => 'Graphic Design', 'ar' => 'التصميم الجرافيكي', 'category' => 'creative',
                'default_blocks' => ['hero', 'gallery', 'projects', 'software_stack', 'reviews'],
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
