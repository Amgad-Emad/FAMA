<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Skills catalog 6 → 9 (client spec "Skills & specialities", ADR-Y).
 *
 * Adds the three skills the spec adds — Direction, Hair & makeup, and
 * Retouching & post — and applies its orthography to the existing rows
 * ("Modeling" → "Modelling", title-case → sentence-case). It runs in place on
 * an existing database so a deploy needs `migrate` alone; TalentTypeSeeder
 * seeds the same nine values on a fresh one, which makes this a no-op there.
 *
 * SLUGS ARE UNCHANGED. `modeling` stays `modeling` even though the display name
 * gains its second L: the slug is the public deep link (`/{talent}?skill=` and
 * `/discover?filter[type]=`) and the ADR-S rename already broke those once. An
 * orthographic fix does not justify breaking them again.
 *
 * Categories for the new skills:
 *  - Direction mirrors Cinematography (the spec says so explicitly), so it takes
 *    the same `crew` category and therefore the same eligible blocks.
 *  - Hair & makeup is `crew` — a HMUA books with a kit, which is the equipment
 *    block.
 *  - Retouching & post is `creative` — the software stack is the proof.
 */
return new class extends Migration
{
    /**
     * Display-name corrections applied to the rows that already exist, keyed by
     * their (unchanged) slug.
     *
     * @var array<string, array{en: string, ar: string}>
     */
    private array $renames = [
        'modeling' => ['en' => 'Modelling', 'ar' => 'عرض الأزياء'],
        'creative-direction' => ['en' => 'Creative direction', 'ar' => 'الإدارة الإبداعية'],
        'graphic-design' => ['en' => 'Graphic design', 'ar' => 'التصميم الجرافيكي'],
    ];

    /**
     * The three skills the spec adds, in catalog order.
     *
     * @var list<array<string, mixed>>
     */
    private array $additions = [
        [
            'slug' => 'direction', 'en' => 'Direction', 'ar' => 'الإخراج', 'category' => 'crew',
            'default_blocks' => ['showreel', 'projects', 'brand_collabs', 'reviews'],
        ],
        [
            'slug' => 'hair-makeup', 'en' => 'Hair & makeup', 'ar' => 'الشعر والمكياج', 'category' => 'crew',
            'default_blocks' => ['gallery', 'equipment', 'brand_collabs', 'reviews'],
        ],
        [
            'slug' => 'retouching-post', 'en' => 'Retouching & post', 'ar' => 'المعالجة وما بعد الإنتاج', 'category' => 'creative',
            'default_blocks' => ['gallery', 'projects', 'software_stack', 'reviews'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->renames as $slug => $name) {
            DB::table('talent_types')->where('slug', $slug)->update([
                'name' => json_encode($name, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }

        $position = (int) DB::table('talent_types')->max('id');

        foreach ($this->additions as $skill) {
            if (DB::table('talent_types')->where('slug', $skill['slug'])->exists()) {
                continue;
            }

            DB::table('talent_types')->insert([
                'name' => json_encode(['en' => $skill['en'], 'ar' => $skill['ar']], JSON_UNESCAPED_UNICODE),
                'slug' => $skill['slug'],
                'category' => $skill['category'],
                'default_blocks' => json_encode($skill['default_blocks']),
                'icon' => 'lucide-'.$skill['slug'],
                'description' => json_encode([
                    'en' => $skill['en'].' on Fama.',
                    'ar' => $skill['ar'].' على فاما.',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $position++;
        }
    }

    /**
     * Reverses the names. The three added skills are NOT deleted — by the time
     * anyone rolls this back, talents may hold them, and dropping the rows would
     * cascade their profile blocks and project tags away.
     */
    public function down(): void
    {
        $previous = [
            'modeling' => ['en' => 'Modeling', 'ar' => 'عرض الأزياء'],
            'creative-direction' => ['en' => 'Creative Direction', 'ar' => 'الإدارة الإبداعية'],
            'graphic-design' => ['en' => 'Graphic Design', 'ar' => 'التصميم الجرافيكي'],
        ];

        foreach ($previous as $slug => $name) {
            DB::table('talent_types')->where('slug', $slug)->update([
                'name' => json_encode($name, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }
};
