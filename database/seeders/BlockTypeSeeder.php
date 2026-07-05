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
            ['key' => 'hero', 'en' => 'Hero', 'ar' => 'الواجهة', 'availability' => 'universal', 'content_source' => 'inline', 'layout' => null, 'repeatable' => false, 'categories' => []],
            ['key' => 'gallery', 'en' => 'Gallery', 'ar' => 'المعرض', 'availability' => 'universal', 'content_source' => 'table', 'layout' => 'masonry', 'repeatable' => true, 'categories' => []],
            ['key' => 'brand_collabs', 'en' => 'Brand Collaborations', 'ar' => 'تعاونات العلامات', 'availability' => 'universal', 'content_source' => 'table', 'layout' => 'grid', 'repeatable' => false, 'categories' => []],
            ['key' => 'reviews', 'en' => 'Reviews', 'ar' => 'التقييمات', 'availability' => 'universal', 'content_source' => 'table', 'layout' => 'list', 'repeatable' => false, 'categories' => []],
            ['key' => 'services', 'en' => 'Services', 'ar' => 'الخدمات', 'availability' => 'universal', 'content_source' => 'table', 'layout' => 'list', 'repeatable' => false, 'categories' => []],
            ['key' => 'comp_card', 'en' => 'Comp Card', 'ar' => 'بطاقة القياسات', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => null, 'repeatable' => false, 'categories' => ['model']],
            ['key' => 'look_types', 'en' => 'Looks', 'ar' => 'الإطلالات', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => 'list', 'repeatable' => false, 'categories' => ['model']],
            ['key' => 'digitals', 'en' => 'Digitals', 'ar' => 'الصور الأولية', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => 'grid', 'repeatable' => false, 'categories' => ['model']],
            ['key' => 'showreel', 'en' => 'Showreel', 'ar' => 'العرض المرئي', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => 'carousel', 'repeatable' => true, 'categories' => ['crew', 'creative']],
            ['key' => 'equipment', 'en' => 'Equipment', 'ar' => 'المعدات', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => 'list', 'repeatable' => false, 'categories' => ['crew']],
            ['key' => 'case_studies', 'en' => 'Case Studies', 'ar' => 'دراسات الحالة', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => 'list', 'repeatable' => true, 'categories' => ['crew', 'creative']],
            ['key' => 'software_stack', 'en' => 'Software', 'ar' => 'البرمجيات', 'availability' => 'by_category', 'content_source' => 'table', 'layout' => 'grid', 'repeatable' => false, 'categories' => ['creative']],
            ['key' => 'agency_affiliations', 'en' => 'Agencies', 'ar' => 'الوكالات', 'availability' => 'universal', 'content_source' => 'table', 'layout' => 'list', 'repeatable' => false, 'categories' => []],
            ['key' => 'press_features', 'en' => 'Press', 'ar' => 'الصحافة', 'availability' => 'universal', 'content_source' => 'table', 'layout' => 'grid', 'repeatable' => false, 'categories' => []],
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
                    'is_active' => true,
                    'is_repeatable' => $entry['repeatable'],
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
