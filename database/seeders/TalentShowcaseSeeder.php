<?php

namespace Database\Seeders;

use App\Models\BlockType;
use App\Models\BrandCollab;
use App\Models\CaseStudy;
use App\Models\CompCard;
use App\Models\Digital;
use App\Models\Equipment;
use App\Models\LookType;
use App\Models\ProfileBlock;
use App\Models\Review;
use App\Models\Service;
use App\Models\Showreel;
use App\Models\SoftwareStack;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\Concerns\GeneratesCoverImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ten showcase talents spanning all six professions — single- and multi-type,
 * so the discovery feed and profiles render with varied structures and real,
 * curated data. Hero/avatar/gallery images are generated locally (GD gradient
 * covers) and attached through medialibrary, so profiles show actual images.
 *
 * Idempotent: each showcase talent is rebuilt by email. Requires TalentTypeSeeder
 * + BlockTypeSeeder.
 */
class TalentShowcaseSeeder extends Seeder
{
    use GeneratesCoverImages;

    public function run(): void
    {
        $types = TalentType::all()->keyBy('slug');

        foreach ($this->talents() as $spec) {
            DB::transaction(fn () => $this->build($spec, $types));
        }
    }

    /**
     * @param  array<string, mixed>  $spec
     * @param  Collection<string, TalentType>  $types
     */
    private function build(array $spec, $types): void
    {
        Talent::withTrashed()->where('email', $spec['email'])->forceDelete();

        $talent = Talent::factory()->create([
            'email' => $spec['email'],
            'slug' => $spec['slug'],
            'display_name' => $spec['name'],
            'headline' => $spec['headline'],
            'bio' => $spec['bio'],
            'base_city' => $spec['city'],
            'base_country' => $spec['country'],
            'availability_status' => $spec['availability'],
            'rate_tier' => $spec['tier'],
            'willing_to_travel' => $spec['travel'] ?? true,
            'is_published' => true,
        ]);

        $sync = [];
        foreach ($spec['types'] as $pos => $slug) {
            $sync[$types[$slug]->id] = ['is_primary' => $pos === 0, 'position' => $pos];
        }
        $talent->talentTypes()->sync($sync);

        $galleryBlock = $this->seedBlocks($talent, $spec['types'], $types);

        // Images: hero + avatar always; gallery items get their own covers.
        $talent->addMedia($this->cover($spec['email'].'-hero', 1280, 860))->toMediaCollection('hero');
        $talent->addMedia($this->cover($spec['email'].'-avatar', 640, 640))->toMediaCollection('avatar');

        $this->seedContent($talent, $spec, $galleryBlock);
    }

    /**
     * Merge the default blocks of the talent's types and seed profile_blocks.
     * Returns the gallery block (if any) for portfolio items.
     *
     * @param  list<string>  $typeSlugs
     * @param  Collection<string, TalentType>  $types
     */
    private function seedBlocks(Talent $talent, array $typeSlugs, $types): ?ProfileBlock
    {
        $keys = collect($typeSlugs)
            ->flatMap(fn ($slug) => $types[$slug]->default_blocks ?? [])
            ->unique()
            ->values();

        $blockTypes = BlockType::whereIn('key', $keys)->get()->keyBy('key');
        $gallery = null;
        $position = 0;

        foreach ($keys as $key) {
            $blockType = $blockTypes->get($key);
            if ($blockType === null) {
                continue;
            }

            $block = $talent->profileBlocks()->create([
                'block_type_id' => $blockType->id,
                'title' => $blockType->getTranslations('name'),
                'position' => $position++,
                'is_visible' => true,
                'layout' => $blockType->default_layout,
                'settings' => [],
                'content' => null,
            ]);

            if ($key === 'gallery') {
                $gallery = $block;
            }
        }

        return $gallery;
    }

    /**
     * Populate the content tables appropriate to the talent's category, with
     * generated gallery images.
     *
     * @param  array<string, mixed>  $spec
     */
    private function seedContent(Talent $talent, array $spec, ?ProfileBlock $galleryBlock): void
    {
        $category = $spec['category'];
        $rich = ($spec['depth'] ?? 'rich') === 'rich';

        // Gallery — every talent (images attached).
        foreach ($spec['gallery'] as $i => $caption) {
            if (! $rich && $i >= 3) {
                break;
            }
            $item = $talent->portfolioItems()->create([
                'block_id' => $galleryBlock?->id,
                'media_type' => 'image',
                'caption' => $caption,
                'position' => $i,
                'status' => 'uploaded',
            ]);
            $item->addMedia($this->cover($spec['email'].'-g'.$i, 900, 1120))->toMediaCollection('gallery');
        }

        // Services — every talent.
        foreach ($spec['services'] as $i => $service) {
            Service::factory()->for($talent)->create($service + ['currency' => 'EGP', 'is_active' => true, 'position' => $i]);
        }

        // Reviews — a couple, curated.
        foreach ($spec['reviews'] as $review) {
            Review::factory()->for($talent)->create($review + ['is_approved' => true, 'status' => 'approved', 'reviewed_at' => now()]);
        }

        // Category-specific sections.
        if ($category === 'model') {
            CompCard::factory()->for($talent)->create();
            LookType::factory()->count($rich ? 4 : 2)->for($talent)->create();
            Digital::factory()->count($rich ? 5 : 3)->for($talent)->create();
        }

        if ($category === 'crew') {
            Equipment::factory()->count($rich ? 6 : 3)->for($talent)->create();
            Showreel::factory()->count($rich ? 2 : 1)->for($talent)->create();
            SoftwareStack::factory()->count(3)->for($talent)->create();
        }

        if ($category === 'creative') {
            SoftwareStack::factory()->count($rich ? 5 : 3)->for($talent)->create();
            CaseStudy::factory()->count($rich ? 2 : 1)->for($talent)->create();
            BrandCollab::factory()->count($rich ? 4 : 2)->for($talent)->create();
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function talents(): array
    {
        $svc = fn (string $en, string $ar, int $price, string $unit) => ['name' => ['en' => $en, 'ar' => $ar], 'price' => $price, 'price_unit' => $unit];
        $rev = fn (string $name, string $role, string $co, int $rating, string $body, string $type) => ['reviewer_name' => $name, 'reviewer_role' => $role, 'reviewer_company' => $co, 'rating' => $rating, 'body' => $body, 'project_type' => $type];

        return [
            [
                'name' => 'Nour El-Sherif', 'email' => 'nour@fama.test', 'slug' => 'nour-elsherif',
                'types' => ['model'], 'category' => 'model', 'city' => 'Cairo', 'country' => 'Egypt',
                'availability' => 'available', 'tier' => 'established', 'depth' => 'rich',
                'headline' => ['en' => 'Editorial & runway model — Cairo', 'ar' => 'عارضة أزياء تحريرية ومنصات — القاهرة'],
                'bio' => ['en' => 'Runway and editorial model with a decade on MENA fashion weeks.', 'ar' => 'عارضة منصات وتحرير بخبرة عشر سنوات في أسابيع الموضة بالمنطقة.'],
                'gallery' => [['en' => 'Cairo Fashion Week — closing', 'ar' => 'أسبوع القاهرة للموضة — الختام'], ['en' => 'Gold-hour editorial', 'ar' => 'تحرير الساعة الذهبية'], ['en' => 'Beauty close-up', 'ar' => 'لقطة جمال قريبة'], ['en' => 'Studio, high-key', 'ar' => 'استوديو، إضاءة عالية']],
                'services' => [$svc('Runway — per show', 'منصة — لكل عرض', 12000, 'project'), $svc('Editorial — full day', 'تحرير — يوم كامل', 16000, 'day')],
                'reviews' => [$rev('Dina Alaa', 'Fashion Editor', 'Élan Magazine', 5, 'Commands the runway and the frame in equal measure.', 'Editorial')],
            ],
            [
                'name' => 'Karim Mansour', 'email' => 'karim@fama.test', 'slug' => 'karim-mansour',
                'types' => ['photographer'], 'category' => 'crew', 'city' => 'Alexandria', 'country' => 'Egypt',
                'availability' => 'available', 'tier' => 'premium', 'depth' => 'rich',
                'headline' => ['en' => 'Fashion & commercial photographer — Alexandria', 'ar' => 'مصور أزياء وتجاري — الإسكندرية'],
                'bio' => ['en' => 'Commercial and campaign photographer working across the Mediterranean coast.', 'ar' => 'مصور حملات وتجاري يعمل على امتداد ساحل المتوسط.'],
                'gallery' => [['en' => 'Linen SS26 campaign', 'ar' => 'حملة الكتان ربيع/صيف ٢٦'], ['en' => 'Corniche at dawn', 'ar' => 'الكورنيش عند الفجر'], ['en' => 'Product still life', 'ar' => 'تصوير منتجات ثابت'], ['en' => 'Portrait, natural light', 'ar' => 'بورتريه بإضاءة طبيعية']],
                'services' => [$svc('Campaign shoot — full day', 'تصوير حملة — يوم كامل', 22000, 'day'), $svc('Product photography — per set', 'تصوير منتجات — لكل مجموعة', 4000, 'project')],
                'reviews' => [$rev('Sherif Gaber', 'Art Director', 'Coastline Studio', 5, 'Impeccable light and a calm, precise set.', 'Campaign')],
            ],
            [
                'name' => 'Yasmin Adel', 'email' => 'yasmin@fama.test', 'slug' => 'yasmin-adel',
                'types' => ['model', 'stylist'], 'category' => 'model', 'city' => 'Cairo', 'country' => 'Egypt',
                'availability' => 'booked', 'tier' => 'established', 'depth' => 'rich',
                'headline' => ['en' => 'Model & stylist — Cairo', 'ar' => 'عارضة وستايلست — القاهرة'],
                'bio' => ['en' => 'Commercial model who also styles her own editorial shoots.', 'ar' => 'عارضة تجارية تتولى أيضاً تنسيق جلساتها التحريرية.'],
                'gallery' => [['en' => 'Self-styled editorial', 'ar' => 'تحرير بتنسيق ذاتي'], ['en' => 'Denim story', 'ar' => 'قصة الدنيم'], ['en' => 'Monochrome set', 'ar' => 'مجموعة أحادية اللون']],
                'services' => [$svc('Commercial modelling — half day', 'عرض تجاري — نصف يوم', 8000, 'day'), $svc('Styling — per shoot', 'تنسيق — لكل جلسة', 6000, 'project')],
                'reviews' => [$rev('Laila Mounir', 'Producer', 'Nile Content', 5, 'Two talents in one booking — effortless.', 'Lookbook')],
            ],
            [
                'name' => 'Omar Khaled', 'email' => 'omar@fama.test', 'slug' => 'omar-khaled',
                'types' => ['cinematographer'], 'category' => 'crew', 'city' => 'Giza', 'country' => 'Egypt',
                'availability' => 'available', 'tier' => 'premium', 'depth' => 'rich',
                'headline' => ['en' => 'Cinematographer / DOP — Giza', 'ar' => 'مدير تصوير سينمائي — الجيزة'],
                'bio' => ['en' => 'DOP for commercials and music videos, ARRI and RED owner-operator.', 'ar' => 'مدير تصوير للإعلانات وفيديوهات الموسيقى، يمتلك ويشغّل ARRI وRED.'],
                'gallery' => [['en' => 'Desert commercial — frame', 'ar' => 'إعلان صحراوي — كادر'], ['en' => 'Night exterior', 'ar' => 'خارجي ليلي'], ['en' => 'Music video still', 'ar' => 'لقطة فيديو موسيقي'], ['en' => 'Handheld chase', 'ar' => 'مطاردة بالكاميرا المحمولة']],
                'services' => [$svc('DOP day rate', 'أجر مدير تصوير يومي', 28000, 'day'), $svc('Camera + operator package', 'باقة كاميرا ومشغّل', 40000, 'project')],
                'reviews' => [$rev('Tamer Nabil', 'Director', 'Pyramide Films', 5, 'Cinematic instincts and rock-steady on the day.', 'Commercial')],
            ],
            [
                'name' => 'Hana Fahmy', 'email' => 'hana@fama.test', 'slug' => 'hana-fahmy',
                'types' => ['creative-director'], 'category' => 'creative', 'city' => 'Dubai', 'country' => 'UAE',
                'availability' => 'available', 'tier' => 'elite', 'depth' => 'rich',
                'headline' => ['en' => 'Creative director — Dubai', 'ar' => 'مديرة إبداعية — دبي'],
                'bio' => ['en' => 'Brand and campaign creative direction for regional launches.', 'ar' => 'إدارة إبداعية للعلامات والحملات لإطلاقات إقليمية.'],
                'gallery' => [['en' => 'Retail launch — key visual', 'ar' => 'إطلاق تجزئة — البصرية الأساسية'], ['en' => 'Brand world board', 'ar' => 'لوحة عالم العلامة'], ['en' => 'Campaign film — grade', 'ar' => 'فيلم حملة — تدرّج لوني']],
                'services' => [$svc('Creative direction — per campaign', 'إدارة إبداعية — لكل حملة', 60000, 'project'), $svc('Brand workshop — day', 'ورشة علامة — يوم', 20000, 'day')],
                'reviews' => [$rev('Rana Kassem', 'Marketing Lead', 'Gulf Retail Group', 5, 'Turned a brief into a whole brand world.', 'Campaign')],
            ],
            [
                'name' => 'Ziad Rahman', 'email' => 'ziad@fama.test', 'slug' => 'ziad-rahman',
                'types' => ['graphic-designer'], 'category' => 'creative', 'city' => 'Cairo', 'country' => 'Egypt',
                'availability' => 'available', 'tier' => 'established', 'depth' => 'lean',
                'headline' => ['en' => 'Graphic designer — Cairo', 'ar' => 'مصمم جرافيك — القاهرة'],
                'bio' => ['en' => 'Identity and packaging designer for food & beverage brands.', 'ar' => 'مصمم هوية وتغليف لعلامات الأغذية والمشروبات.'],
                'gallery' => [['en' => 'Coffee brand identity', 'ar' => 'هوية علامة قهوة'], ['en' => 'Packaging system', 'ar' => 'نظام تغليف'], ['en' => 'Type specimen', 'ar' => 'عينة خطوط']],
                'services' => [$svc('Brand identity — package', 'هوية علامة — باقة', 35000, 'project'), $svc('Packaging — per SKU', 'تغليف — لكل منتج', 3000, 'project')],
                'reviews' => [$rev('Mostafa Selim', 'Founder', 'Bean & Co.', 5, 'Our shelves finally look like us.', 'Branding')],
            ],
            [
                'name' => 'Farida Nabil', 'email' => 'farida@fama.test', 'slug' => 'farida-nabil',
                'types' => ['model'], 'category' => 'model', 'city' => 'Alexandria', 'country' => 'Egypt',
                'availability' => 'booked', 'tier' => 'emerging', 'depth' => 'lean',
                'headline' => ['en' => 'Commercial model — Alexandria', 'ar' => 'عارضة تجارية — الإسكندرية'],
                'bio' => ['en' => 'Fresh commercial face for lifestyle and beauty brands.', 'ar' => 'وجه تجاري جديد لعلامات نمط الحياة والجمال.'],
                'gallery' => [['en' => 'Lifestyle, seaside', 'ar' => 'نمط حياة، بجانب البحر'], ['en' => 'Beauty, soft light', 'ar' => 'جمال، إضاءة ناعمة'], ['en' => 'Catalogue set', 'ar' => 'مجموعة كتالوج']],
                'services' => [$svc('Commercial — half day', 'تجاري — نصف يوم', 5000, 'day')],
                'reviews' => [$rev('Nadia Wahba', 'Casting Director', 'Coast Casting', 4, 'Natural in front of the camera, easy to direct.', 'Commercial')],
            ],
            [
                'name' => 'Tarek Sobhy', 'email' => 'tarek@fama.test', 'slug' => 'tarek-sobhy',
                'types' => ['photographer', 'cinematographer'], 'category' => 'crew', 'city' => 'Cairo', 'country' => 'Egypt',
                'availability' => 'available', 'tier' => 'premium', 'depth' => 'rich',
                'headline' => ['en' => 'Photographer & DOP — Cairo', 'ar' => 'مصور ومدير تصوير — القاهرة'],
                'bio' => ['en' => 'Stills and motion for brands that need one crew for both.', 'ar' => 'تصوير ثابت ومتحرك للعلامات التي تحتاج طاقماً واحداً للاثنين.'],
                'gallery' => [['en' => 'Stills + motion set', 'ar' => 'موقع تصوير ثابت ومتحرك'], ['en' => 'Automotive campaign', 'ar' => 'حملة سيارات'], ['en' => 'Studio tabletop', 'ar' => 'تصوير طاولة في الاستوديو'], ['en' => 'Rooftop interview', 'ar' => 'مقابلة على السطح']],
                'services' => [$svc('Stills + motion — day', 'ثابت ومتحرك — يوم', 26000, 'day'), $svc('Social content — per batch', 'محتوى سوشيال — لكل دفعة', 9000, 'project')],
                'reviews' => [$rev('Injy Farid', 'Brand Manager', 'Motorline', 5, 'One booking, both deliverables, zero fuss.', 'Campaign')],
            ],
            [
                'name' => 'Salma Ibrahim', 'email' => 'salma@fama.test', 'slug' => 'salma-ibrahim',
                'types' => ['stylist'], 'category' => 'creative', 'city' => 'Cairo', 'country' => 'Egypt',
                'availability' => 'unavailable', 'tier' => 'established', 'depth' => 'lean',
                'headline' => ['en' => 'Fashion stylist — Cairo', 'ar' => 'ستايلست أزياء — القاهرة'],
                'bio' => ['en' => 'Editorial and campaign styling with a modern MENA wardrobe.', 'ar' => 'تنسيق تحريري وحملات بخزانة عصرية من المنطقة.'],
                'gallery' => [['en' => 'Editorial rack', 'ar' => 'رفّ تحريري'], ['en' => 'Campaign fitting', 'ar' => 'بروفة حملة'], ['en' => 'Accessories flat-lay', 'ar' => 'إكسسوارات مسطحة']],
                'services' => [$svc('Editorial styling — per shoot', 'تنسيق تحريري — لكل جلسة', 7000, 'project')],
                'reviews' => [$rev('Hoda Zaki', 'Photographer', 'Studio Nile', 5, 'Every look landed. Impeccable taste.', 'Editorial')],
            ],
            [
                'name' => 'Adham Yousef', 'email' => 'adham@fama.test', 'slug' => 'adham-yousef',
                'types' => ['model', 'creative-director'], 'category' => 'model', 'city' => 'Giza', 'country' => 'Egypt',
                'availability' => 'available', 'tier' => 'premium', 'depth' => 'rich',
                'headline' => ['en' => 'Model & creative director — Giza', 'ar' => 'عارض ومدير إبداعي — الجيزة'],
                'bio' => ['en' => 'Model who art-directs his own campaigns end to end.', 'ar' => 'عارض يتولى الإدارة الفنية لحملاته من البداية للنهاية.'],
                'gallery' => [['en' => 'Menswear campaign', 'ar' => 'حملة ملابس رجالية'], ['en' => 'Self-directed editorial', 'ar' => 'تحرير بإدارة ذاتية'], ['en' => 'Streetwear story', 'ar' => 'قصة ستريت وير'], ['en' => 'Studio portrait', 'ar' => 'بورتريه استوديو']],
                'services' => [$svc('Modelling — full day', 'عرض أزياء — يوم كامل', 14000, 'day'), $svc('Model + creative direction', 'عرض وإدارة إبداعية', 30000, 'project')],
                'reviews' => [$rev('Karim Downtown', 'Founder', 'Cairo Denim', 5, 'He models it and elevates the whole concept.', 'Campaign')],
            ],
        ];
    }
}
