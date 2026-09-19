<?php

namespace Database\Seeders;

use App\Models\TalentType;
use Illuminate\Database\Seeder;

/**
 * Seeds the NINE canonical skills (the `talent_types` catalog — ADR-N, expanded
 * to nine by ADR-Y). Skills are named as the DISCIPLINE/ACTIVITY, not the person
 * (ADR-S): "Modelling", not "Model". `default_blocks` (ordered block_type keys)
 * decides which blocks a new talent of that skill gets pre-loaded. `category`
 * stays the DB enum (model/crew/creative) and gates block eligibility.
 * Idempotent on `slug`.
 *
 * The client spec's call-sheet test governs what is in here: a skill is
 * something someone gets PAID as. Techniques, equipment and seniority are not
 * skills and never enter the list. Specialities (the second level) are seeded
 * separately by SpecialitySeeder, which must run after this.
 */
class TalentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'slug' => 'photography', 'en' => 'Photography', 'ar' => 'التصوير الفوتوغرافي', 'category' => 'crew',
                'default_blocks' => ['gallery', 'showreel', 'equipment', 'brand_collabs', 'reviews'],
            ],
            [
                'slug' => 'cinematography', 'en' => 'Cinematography', 'ar' => 'التصوير السينمائي', 'category' => 'crew',
                'default_blocks' => ['showreel', 'equipment', 'projects', 'reviews'],
            ],
            // Deliberately mirrors cinematography — many people hold both, and
            // matching categories make that legible rather than confusing.
            [
                'slug' => 'direction', 'en' => 'Direction', 'ar' => 'الإخراج', 'category' => 'crew',
                'default_blocks' => ['showreel', 'projects', 'brand_collabs', 'reviews'],
            ],
            [
                'slug' => 'modeling', 'en' => 'Modelling', 'ar' => 'عرض الأزياء', 'category' => 'model',
                'default_blocks' => ['gallery', 'comp_card', 'look_types', 'digitals', 'brand_collabs', 'reviews'],
            ],
            [
                'slug' => 'styling', 'en' => 'Styling', 'ar' => 'تنسيق الأزياء', 'category' => 'creative',
                'default_blocks' => ['gallery', 'brand_collabs', 'reviews'],
            ],
            // Nothing gets shot without it, and it is among the most frequently
            // booked roles on any set. `crew` because the kit is the equipment block.
            [
                'slug' => 'hair-makeup', 'en' => 'Hair & makeup', 'ar' => 'الشعر والمكياج', 'category' => 'crew',
                'default_blocks' => ['gallery', 'equipment', 'brand_collabs', 'reviews'],
            ],
            [
                'slug' => 'creative-direction', 'en' => 'Creative direction', 'ar' => 'الإدارة الإبداعية', 'category' => 'creative',
                'default_blocks' => ['gallery', 'projects', 'brand_collabs', 'reviews'],
            ],
            [
                'slug' => 'graphic-design', 'en' => 'Graphic design', 'ar' => 'التصميم الجرافيكي', 'category' => 'creative',
                'default_blocks' => ['gallery', 'projects', 'software_stack', 'reviews'],
            ],
            // Booked separately often enough to be its own skill, not a service
            // bundled under photography.
            [
                'slug' => 'retouching-post', 'en' => 'Retouching & post', 'ar' => 'المعالجة وما بعد الإنتاج', 'category' => 'creative',
                'default_blocks' => ['gallery', 'projects', 'software_stack', 'reviews'],
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
