<?php

namespace Database\Seeders;

use App\Models\BlockType;
use Illuminate\Database\Seeder;

/**
 * Seeds the admin-governed block catalog (block_types + block_type_category).
 * Idempotent: keyed on `block_types.key`. Availability gates which talents may
 * add each block (universal = anyone; by_category = only those categories).
 */
class BlockTypeSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            // RETIRED (ADR-O): the cover/hero region became the avatar-led header,
            // which the profile renders itself — a hero block showed an editable
            // row that rendered nothing. Kept in the catalog (keys are frozen)
            // but INACTIVE, so it is never seeded, picked, or placed again.
            ['key' => 'hero', 'en' => 'Hero', 'ar' => 'الواجهة', 'availability' => 'universal', 'content_source' => 'inline', 'layout' => null, 'repeatable' => false, 'categories' => [], 'active' => false],
            ['key' => 'gallery', 'en' => 'Gallery', 'ar' => 'المعرض', 'availability' => 'universal', 'content_source' => 'table', 'layout' => 'masonry', 'repeatable' => true, 'categories' => []],
            ['key' => 'brand_collabs', 'en' => 'Brand Collaborations', 'ar' => 'تعاونات العلامات', 'availability' => 'universal', 'content_source' => 'table', 'layout' => 'grid', 'repeatable' => false, 'categories' => []],
            ['key' => 'reviews', 'en' => 'Reviews', 'ar' => 'التقييمات', 'availability' => 'universal', 'content_source' => 'table', 'layout' => 'list', 'repeatable' => false, 'categories' => []],
            // ADR-Z. Recognitions are peer vouches (talent → talent) and sit
            // BESIDE reviews (brand → talent), never merged with them: if both
            // render as one wall of praise, neither gets read.
            ['key' => 'recognitions', 'en' => 'Recognitions', 'ar' => 'تزكيات الزملاء', 'availability' => 'universal', 'content_source' => 'table', 'layout' => 'list', 'repeatable' => false, 'categories' => [], 'profile_default' => true],
            // ADR-Z. The one section that asks for something instead of proving something.
            ['key' => 'interests', 'en' => 'Willing to explore', 'ar' => 'منفتح على', 'availability' => 'universal', 'content_source' => 'table', 'layout' => 'list', 'repeatable' => false, 'categories' => [], 'profile_default' => true],
            ['key' => 'comp_card', 'en' => 'Comp Card', 'ar' => 'بطاقة القياسات', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => null, 'repeatable' => false, 'categories' => ['model']],
            ['key' => 'look_types', 'en' => 'Looks', 'ar' => 'الإطلالات', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => 'list', 'repeatable' => false, 'categories' => ['model']],
            ['key' => 'digitals', 'en' => 'Digitals', 'ar' => 'الصور الأولية', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => 'grid', 'repeatable' => false, 'categories' => ['model']],
            ['key' => 'showreel', 'en' => 'Showreel', 'ar' => 'العرض المرئي', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => 'carousel', 'repeatable' => true, 'categories' => ['model', 'crew', 'creative']],
            ['key' => 'equipment', 'en' => 'Equipment', 'ar' => 'المعدات', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => 'list', 'repeatable' => false, 'categories' => ['crew']],
            ['key' => 'projects', 'en' => 'Projects', 'ar' => 'المشاريع', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => 'list', 'repeatable' => true, 'categories' => ['model', 'crew', 'creative']],
            ['key' => 'software_stack', 'en' => 'Software', 'ar' => 'البرمجيات', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => 'grid', 'repeatable' => false, 'categories' => ['creative']],
        ];

        foreach ($catalog as $position => $entry) {
            $blockType = BlockType::updateOrCreate(
                ['key' => $entry['key']],
                [
                    'name' => ['en' => $entry['en'], 'ar' => $entry['ar']],
                    'icon' => 'lucide-'.str_replace('_', '-', $entry['key']),
                    'availability' => $entry['availability'],
                    'content_source' => $entry['content_source'],
                    'default_layout' => $entry['layout'],
                    'is_active' => $entry['active'] ?? true,
                    'is_repeatable' => $entry['repeatable'],
                    // Seeded ONCE at profile level rather than per skill tab (ADR-Z).
                    'is_profile_default' => $entry['profile_default'] ?? false,
                    'position' => $position,
                ],
            );

            // Refresh the category gates for by_category blocks.
            $blockType->categories()->delete();
            foreach ($entry['categories'] as $category) {
                $blockType->categories()->create(['category' => $category]);
            }
        }
    }
}
