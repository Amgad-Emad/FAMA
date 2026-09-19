<?php

namespace Database\Seeders;

use App\Models\Speciality;
use App\Models\TalentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the controlled speciality vocabulary — the second and final level of the
 * skills tree (client spec "Skills & specialities", ADR-Y). Must run AFTER
 * TalentTypeSeeder; idempotent on (talent_type_id, slug).
 *
 * The rules the list was built to, kept here because they decide what may be
 * added later:
 *  - ONE AXIS. Every entry answers "what kind of subject". Output format
 *    (campaign, editorial, lookbook, e-commerce) belongs to the project.
 *  - FOUR TO SIX. Under four and the skill did not need splitting; over six and
 *    nobody reads to the bottom. Photography earns a seventh — it carries the
 *    widest range of genuinely different commercial work.
 *  - SEARCHABLE. Every entry is a phrase a brand would type. Insider vocabulary
 *    gets cut however accurate it is.
 *  - OVERLAP IS FINE. "Fashion" appears under three skills. That is what lets one
 *    search assemble a whole crew.
 *
 * `regional` flags the entries added for this market specifically. They are not
 * filters applied to a broader category — they are commercial categories with
 * their own clients and casting logic in KSA and the UAE.
 */
class SpecialitySeeder extends Seeder
{
    /**
     * skill slug => ordered specialities. `regional` defaults to false.
     *
     * @var array<string, list<array<string, mixed>>>
     */
    private array $catalog = [
        'photography' => [
            ['en' => 'Fashion', 'ar' => 'الأزياء'],
            ['en' => 'Beauty', 'ar' => 'الجمال'],
            ['en' => 'Product & still life', 'ar' => 'المنتجات والطبيعة الصامتة'],
            ['en' => 'Food', 'ar' => 'الطعام'],
            ['en' => 'Portrait', 'ar' => 'البورتريه'],
            ['en' => 'Event & documentary', 'ar' => 'الفعاليات والتوثيق'],
            ['en' => 'Interiors & architecture', 'ar' => 'العمارة والتصميم الداخلي'],
        ],
        'cinematography' => [
            ['en' => 'Commercial', 'ar' => 'الإعلانات'],
            ['en' => 'Fashion film', 'ar' => 'أفلام الأزياء'],
            ['en' => 'Music video', 'ar' => 'الفيديو الموسيقي'],
            ['en' => 'Documentary', 'ar' => 'الأفلام الوثائقية'],
        ],
        'direction' => [
            ['en' => 'Commercial', 'ar' => 'الإعلانات'],
            ['en' => 'Fashion film', 'ar' => 'أفلام الأزياء'],
            ['en' => 'Music video', 'ar' => 'الفيديو الموسيقي'],
            ['en' => 'Documentary', 'ar' => 'الأفلام الوثائقية'],
            ['en' => 'Social & short form', 'ar' => 'المحتوى القصير والاجتماعي'],
        ],
        'modeling' => [
            ['en' => 'Fashion', 'ar' => 'الأزياء'],
            ['en' => 'Commercial & lifestyle', 'ar' => 'الإعلانات ونمط الحياة'],
            ['en' => 'Beauty', 'ar' => 'الجمال'],
            ['en' => 'Runway', 'ar' => 'عروض الأزياء'],
            ['en' => 'Modest fashion', 'ar' => 'الأزياء المحتشمة', 'regional' => true],
            ['en' => 'Fitness', 'ar' => 'اللياقة'],
        ],
        'styling' => [
            ['en' => 'Fashion', 'ar' => 'الأزياء'],
            ['en' => 'Commercial wardrobe', 'ar' => 'أزياء الإعلانات'],
            ['en' => 'Modest fashion', 'ar' => 'الأزياء المحتشمة', 'regional' => true],
            ['en' => 'Set & prop', 'ar' => 'الديكور والإكسسوار'],
            ['en' => 'Food', 'ar' => 'الطعام'],
        ],
        'hair-makeup' => [
            ['en' => 'Beauty makeup', 'ar' => 'مكياج الجمال'],
            ['en' => 'Editorial makeup', 'ar' => 'مكياج التحرير'],
            ['en' => 'Hair styling', 'ar' => 'تصفيف الشعر'],
            ['en' => 'Grooming', 'ar' => 'العناية الرجالية'],
            ['en' => 'Special effects', 'ar' => 'المؤثرات الخاصة'],
        ],
        'creative-direction' => [
            ['en' => 'Campaign', 'ar' => 'الحملات'],
            ['en' => 'Brand world', 'ar' => 'عالم العلامة'],
            ['en' => 'Content & social', 'ar' => 'المحتوى والتواصل'],
            ['en' => 'Casting', 'ar' => 'اختيار المواهب'],
        ],
        'graphic-design' => [
            ['en' => 'Brand identity', 'ar' => 'الهوية البصرية'],
            ['en' => 'Packaging', 'ar' => 'التغليف'],
            ['en' => 'Arabic lettering', 'ar' => 'الحروفية العربية', 'regional' => true],
            ['en' => 'Editorial & layout', 'ar' => 'التحرير والإخراج الفني'],
            ['en' => 'Digital & social', 'ar' => 'الرقمي والتواصل'],
        ],
        'retouching-post' => [
            ['en' => 'Photo retouching', 'ar' => 'معالجة الصور'],
            ['en' => 'Colour grading', 'ar' => 'تصحيح الألوان'],
            ['en' => 'Video editing', 'ar' => 'مونتاج الفيديو'],
            ['en' => 'Motion graphics', 'ar' => 'الموشن جرافيك'],
        ],
    ];

    public function run(): void
    {
        $types = TalentType::query()->pluck('id', 'slug');

        foreach ($this->catalog as $skillSlug => $specialities) {
            $talentTypeId = $types[$skillSlug] ?? null;

            // A skill an operator renamed or removed must not abort the seed.
            if ($talentTypeId === null) {
                $this->command?->warn("SpecialitySeeder: no skill [{$skillSlug}] — skipped.");

                continue;
            }

            foreach ($specialities as $position => $speciality) {
                Speciality::updateOrCreate(
                    ['talent_type_id' => $talentTypeId, 'slug' => Str::slug($speciality['en'])],
                    [
                        'name' => ['en' => $speciality['en'], 'ar' => $speciality['ar']],
                        'is_regional' => $speciality['regional'] ?? false,
                        'is_active' => true,
                        'position' => $position,
                    ],
                );
            }
        }
    }
}
