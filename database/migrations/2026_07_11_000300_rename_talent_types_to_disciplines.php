<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename the six talent_types (the Skills catalog) so a skill reads as the
 * DISCIPLINE/ACTIVITY, not the person (ADR-S): "Modeling", not "Model". Only
 * `name` (translatable en+ar), `slug`, `icon` (slug-derived) and `description`
 * change — **IDs are unchanged**, so every FK (talent_talent_type,
 * block_type_talent_type, brand_project_talent_types, brand_creative_need_talent_type,
 * profile_blocks.talent_type_id, projects.talent_type_id) is untouched.
 *
 * On a fresh `migrate:fresh --seed` this is a no-op (talent_types is empty when the
 * migration runs; the seeder produces the new names). It renames already-seeded
 * databases. Old `?skill=` deep links break — accepted pre-launch (no redirects).
 */
return new class extends Migration
{
    /** old slug => [new slug, en, ar] */
    private const MAP = [
        'model' => ['slug' => 'modeling', 'en' => 'Modeling', 'ar' => 'عرض الأزياء'],
        'photographer' => ['slug' => 'photography', 'en' => 'Photography', 'ar' => 'التصوير الفوتوغرافي'],
        'cinematographer' => ['slug' => 'cinematography', 'en' => 'Cinematography', 'ar' => 'التصوير السينمائي'],
        'creative-director' => ['slug' => 'creative-direction', 'en' => 'Creative Direction', 'ar' => 'الإدارة الإبداعية'],
        'stylist' => ['slug' => 'styling', 'en' => 'Styling', 'ar' => 'تنسيق الأزياء'],
        'graphic-designer' => ['slug' => 'graphic-design', 'en' => 'Graphic Design', 'ar' => 'التصميم الجرافيكي'],
    ];

    public function up(): void
    {
        // Atomic: all six rows rename together or none do (multi-write op).
        DB::transaction(function (): void {
            foreach (self::MAP as $old => $new) {
                $this->rename($old, $new['slug'], $new['en'], $new['ar']);
            }
        });
    }

    public function down(): void
    {
        // Reverse to the pre-rename person nouns.
        $labels = [
            'model' => 'Model', 'photographer' => 'Photographer', 'cinematographer' => 'Cinematographer (DOP)',
            'creative-director' => 'Creative Director', 'stylist' => 'Stylist', 'graphic-designer' => 'Graphic Designer',
        ];
        $arLabels = [
            'model' => 'عارض/عارضة', 'photographer' => 'مصور', 'cinematographer' => 'مدير تصوير',
            'creative-director' => 'مدير إبداعي', 'stylist' => 'ستايليست', 'graphic-designer' => 'مصمم جرافيك',
        ];

        DB::transaction(function () use ($labels, $arLabels): void {
            foreach (self::MAP as $old => $new) {
                $this->rename($new['slug'], $old, $labels[$old], $arLabels[$old]);
            }
        });
    }

    private function rename(string $fromSlug, string $toSlug, string $en, string $ar): void
    {
        DB::table('talent_types')->where('slug', $fromSlug)->update([
            'slug' => $toSlug,
            'name' => json_encode(['en' => $en, 'ar' => $ar], JSON_UNESCAPED_UNICODE),
            'icon' => 'lucide-'.$toSlug,
            'description' => json_encode(['en' => $en.' on Fama.', 'ar' => $ar.' على فاما.'], JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }
};
