<?php

namespace Database\Seeders;

use App\Models\BlockType;
use App\Models\Brand;
use App\Models\BrandCollab;
use App\Models\CompCard;
use App\Models\DealFlow;
use App\Models\Digital;
use App\Models\Equipment;
use App\Models\LookType;
use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\Review;
use App\Models\Service;
use App\Models\Showreel;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\DealService;
use Database\Seeders\Concerns\GeneratesCoverImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Produces one rich, multi-type demo talent (model + photographer) so later
 * phases have something real to render: two professions, the merged+deduped
 * default blocks seeded into profile_blocks, and populated content tables.
 *
 * Idempotent: the demo talent is rebuilt from scratch each run (children cascade
 * away on delete). Requires TalentTypeSeeder + BlockTypeSeeder to have run.
 */
class TalentDemoSeeder extends Seeder
{
    use GeneratesCoverImages;

    private const EMAIL = 'demo.talent@fama.test';

    public function run(): void
    {
        DB::transaction(function (): void {
            // Rebuild from clean state (FK cascade removes children/pivots).
            Talent::withTrashed()->where('email', self::EMAIL)->forceDelete();

            $model = TalentType::where('slug', 'model')->firstOrFail();
            $photographer = TalentType::where('slug', 'photographer')->firstOrFail();

            $talent = Talent::factory()->available()->create([
                'email' => self::EMAIL,
                'slug' => 'demo-talent',
                'display_name' => 'Layla Hassan',
                'headline' => ['en' => 'Model & Photographer — Cairo', 'ar' => 'عارضة ومصورة — القاهرة'],
                'bio' => ['en' => 'Editorial model and commercial photographer based in Cairo.', 'ar' => 'عارضة تحريرية ومصورة تجارية مقرها القاهرة.'],
                'base_city' => 'Cairo',
                'base_country' => 'Egypt',
                'rate_tier' => 'established',
                'is_published' => true,
            ]);

            // Two professions; model leads the headline.
            $talent->talentTypes()->sync([
                $model->id => ['is_primary' => true, 'position' => 0],
                $photographer->id => ['is_primary' => false, 'position' => 1],
            ]);

            // Hero + avatar images.
            $talent->addMedia($this->cover('layla-hero', 1280, 860))->toMediaCollection('hero');
            $talent->addMedia($this->cover('layla-avatar', 640, 640))->toMediaCollection('avatar');

            // Merge + dedupe the default blocks of both types, preserving order.
            $keys = collect($model->default_blocks)
                ->merge($photographer->default_blocks)
                ->unique()
                ->values();

            $blockTypes = BlockType::whereIn('key', $keys)->get()->keyBy('key');

            $position = 0;
            foreach ($keys as $key) {
                $blockType = $blockTypes->get($key);
                if ($blockType === null) {
                    continue;
                }

                $talent->profileBlocks()->create([
                    'block_type_id' => $blockType->id,
                    'title' => $blockType->getTranslations('name'),
                    'position' => $position++,
                    'is_visible' => true,
                    'layout' => $blockType->default_layout,
                    'settings' => [],
                    'content' => null,
                ]);
            }

            // Populate the content tables the demo profile renders.
            $galleryBlock = $talent->profileBlocks()
                ->whereRelation('blockType', 'key', 'gallery')
                ->first();

            // Gallery — curated captions.
            $captions = [
                ['en' => 'Vogue Arabia — editorial', 'ar' => 'فوغ العربية — تحريري'],
                ['en' => 'Nefertari SS26 lookbook', 'ar' => 'لوك بوك نفرتاري ربيع/صيف ٢٦'],
                ['en' => 'Golden hour, El Gouna', 'ar' => 'الساعة الذهبية، الجونة'],
                ['en' => 'Studio beauty series', 'ar' => 'سلسلة جمال الاستوديو'],
                ['en' => 'Cairo rooftop, dusk', 'ar' => 'سطح القاهرة، الغسق'],
                ['en' => 'Linen campaign still', 'ar' => 'لقطة حملة الكتان'],
            ];
            foreach ($captions as $i => $caption) {
                $item = PortfolioItem::factory()->for($talent)->create([
                    'block_id' => $galleryBlock?->id, 'media_type' => 'image', 'caption' => $caption, 'position' => $i,
                ]);
                $item->addMedia($this->cover('layla-g'.$i, 900, 1120))->toMediaCollection('gallery');
            }

            CompCard::factory()->for($talent)->create();               // model
            LookType::factory()->count(4)->for($talent)->create();     // model
            Digital::factory()->count(5)->for($talent)->create();      // model
            Equipment::factory()->count(6)->for($talent)->create();    // photographer
            Showreel::factory()->count(2)->for($talent)->create();     // photographer

            // Projects — curated.
            Project::factory()->for($talent)->create([
                'title' => ['en' => 'Nomad Coffee — Autumn launch', 'ar' => 'نوماد كوفي — إطلاق الخريف'],
                'client_name' => 'Nomad Coffee Co.', 'year' => 2025, 'position' => 0,
                'role' => ['en' => 'Lead model & stills', 'ar' => 'عارضة رئيسية وتصوير ثابت'],
                'summary' => ['en' => 'A two-day lifestyle shoot introducing the autumn menu across three Cairo locations.', 'ar' => 'تصوير لايف ستايل على مدار يومين لتقديم قائمة الخريف في ثلاثة مواقع بالقاهرة.'],
                'results' => ['Reach' => '2.4M', 'Engagement' => '+38%', 'Assets' => '60+'],
            ]);
            Project::factory()->for($talent)->create([
                'title' => ['en' => 'Nefertari Cosmetics — SS26', 'ar' => 'مستحضرات نفرتاري — ربيع/صيف ٢٦'],
                'client_name' => 'Nefertari Cosmetics', 'year' => 2026, 'position' => 1,
                'role' => ['en' => 'Beauty model', 'ar' => 'عارضة جمال'],
                'summary' => ['en' => 'Studio beauty campaign for the spring colour range.', 'ar' => 'حملة جمال في الاستوديو لمجموعة ألوان الربيع.'],
                'results' => ['Reach' => '1.1M', 'Stores' => '40'],
            ]);

            // Brand collaborations.
            foreach (['Nomad Coffee Co.', 'Nefertari Cosmetics', 'El Gouna Resorts', 'Cairo Linen'] as $i => $brandName) {
                BrandCollab::factory()->for($talent)->create(['brand_name' => $brandName, 'year' => 2024 + ($i % 2), 'position' => $i]);
            }

            // Rate card — curated services.
            $services = [
                ['name' => ['en' => 'Editorial modelling — full day', 'ar' => 'عرض أزياء تحريري — يوم كامل'], 'price' => 18000, 'price_unit' => 'day'],
                ['name' => ['en' => 'Commercial photography — half day', 'ar' => 'تصوير تجاري — نصف يوم'], 'price' => 9000, 'price_unit' => 'day'],
                ['name' => ['en' => 'Lookbook shoot — per look', 'ar' => 'تصوير لوك بوك — لكل إطلالة'], 'price' => 1500, 'price_unit' => 'project'],
            ];
            foreach ($services as $i => $service) {
                Service::factory()->for($talent)->create($service + ['currency' => 'EGP', 'is_active' => true, 'position' => $i]);
            }

            // Reviews — curated (3 approved + 1 pending).
            $reviews = [
                ['reviewer_name' => 'Mariam Fouad', 'reviewer_role' => 'Creative Director', 'reviewer_company' => 'Studio Nile', 'rating' => 5, 'body' => "Layla is a director's dream — prepared, expressive, and endlessly patient on set.", 'project_type' => 'Editorial'],
                ['reviewer_name' => 'Omar Sherif', 'reviewer_role' => 'Brand Manager', 'reviewer_company' => 'Nomad Coffee', 'rating' => 5, 'body' => 'She brought our autumn campaign to life and delivered the stills a week early.', 'project_type' => 'Campaign'],
                ['reviewer_name' => 'Yara Adel', 'reviewer_role' => 'Stylist', 'reviewer_company' => 'Freelance', 'rating' => 4, 'body' => 'Great energy and range across looks. Would happily book again.', 'project_type' => 'Lookbook'],
            ];
            foreach ($reviews as $review) {
                Review::factory()->for($talent)->create($review + ['is_approved' => true, 'status' => 'approved', 'reviewed_at' => now()]);
            }
            Review::factory()->pending()->for($talent)->create([
                'reviewer_name' => 'Hana Mostafa', 'reviewer_role' => 'Producer', 'reviewer_company' => 'Cairo Films', 'rating' => 5, 'body' => 'Professional and warm — a pleasure from call sheet to wrap.', 'project_type' => 'Commercial',
            ]);

            // A couple of deals at different steps so the inbox, room and
            // dashboard demo the whole lifecycle for manual QA.
            $flow = DealFlow::where('slug', 'standard-booking')->first();
            if ($flow !== null) {
                $deals = app(DealService::class);
                $brand = fn (string $slug, string $name) => Brand::firstOrCreate(
                    ['email' => $slug.'@fama.test'],
                    ['password' => Hash::make('password'), 'name' => $name, 'slug' => $slug, 'is_complete' => true, 'is_active' => true],
                );
                $start = fn (Brand $b, string $title, string $brief) => $deals->initiate(
                    ['brand_id' => $b->id, 'talent_id' => $talent->id, 'title' => $title, 'initiated_by' => 'brand', 'brief' => $brief],
                    $flow,
                );
                $brief = ['fields' => ['scope' => 'Lifestyle shoot', 'dates' => 'Oct 12–13', 'budget' => 'EGP 40,000']];

                // 1) awaiting_talent — brand submitted the brief; talent must quote.
                $nomad = $brand('nomad-coffee', 'Nomad Coffee Co.');
                $d1 = $start($nomad, 'Autumn campaign shoot', 'Two-day lifestyle shoot for the autumn menu launch across three Cairo locations.');
                $deals->advance($d1, $brief, 'brand', $nomad);

                // 2) awaiting_brand — talent quoted; brand must approve.
                $nefertari = $brand('nefertari-cosmetics', 'Nefertari Cosmetics');
                $d2 = $start($nefertari, 'Spring beauty campaign', 'Studio beauty campaign for the spring colour range.');
                $deals->advance($d2, $brief, 'brand', $nefertari);
                $deals->advance($d2, ['fields' => ['amount' => 32000, 'note' => 'Includes light retouching']], 'talent', $talent);

                // 3) completed — the full loop, front to back.
                $gouna = $brand('el-gouna-resorts', 'El Gouna Resorts');
                $d3 = $start($gouna, 'Resort lookbook', 'On-location lookbook across the marina and beach.');
                $deals->advance($d3, $brief, 'brand', $gouna);
                $deals->advance($d3, ['fields' => ['amount' => 25000]], 'talent', $talent);
                $deals->advance($d3, ['note' => 'Approved'], 'brand', $gouna);
                $deals->skip($d3, 'brand', $gouna);
                $deals->advance($d3, ['attachments' => ['final-delivery.zip']], 'talent', $talent);
                $deals->advance($d3, ['note' => 'Beautiful work'], 'brand', $gouna);
            }
        });
    }
}
