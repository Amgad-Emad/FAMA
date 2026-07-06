<?php

namespace Database\Seeders;

use App\Models\BlockType;
use App\Models\Brand;
use App\Models\BrandCollab;
use App\Models\CaseStudy;
use App\Models\CompCard;
use App\Models\DealFlow;
use App\Models\Digital;
use App\Models\Equipment;
use App\Models\LookType;
use App\Models\PortfolioItem;
use App\Models\Review;
use App\Models\Service;
use App\Models\Showreel;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\DealService;
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
                PortfolioItem::factory()->for($talent)->create([
                    'block_id' => $galleryBlock?->id, 'media_type' => 'image', 'caption' => $caption, 'position' => $i,
                ]);
            }

            CompCard::factory()->for($talent)->create();               // model
            LookType::factory()->count(4)->for($talent)->create();     // model
            Digital::factory()->count(5)->for($talent)->create();      // model
            Equipment::factory()->count(6)->for($talent)->create();    // photographer
            Showreel::factory()->count(2)->for($talent)->create();     // photographer

            // Projects (case studies) — curated.
            CaseStudy::factory()->for($talent)->create([
                'title' => ['en' => 'Nomad Coffee — Autumn launch', 'ar' => 'نوماد كوفي — إطلاق الخريف'],
                'client_name' => 'Nomad Coffee Co.', 'year' => 2025, 'position' => 0,
                'role' => ['en' => 'Lead model & stills', 'ar' => 'عارضة رئيسية وتصوير ثابت'],
                'summary' => ['en' => 'A two-day lifestyle shoot introducing the autumn menu across three Cairo locations.', 'ar' => 'تصوير لايف ستايل على مدار يومين لتقديم قائمة الخريف في ثلاثة مواقع بالقاهرة.'],
                'results' => ['Reach' => '2.4M', 'Engagement' => '+38%', 'Assets' => '60+'],
            ]);
            CaseStudy::factory()->for($talent)->create([
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

            // A live deal in progress so the deal room + inbox demo the lifecycle
            // (brand submitted the brief → now it's the talent's turn to quote).
            $flow = DealFlow::where('slug', 'standard-booking')->first();
            if ($flow !== null) {
                $brand = Brand::firstOrCreate(['email' => 'demo.brand@fama.test'], [
                    'password' => Hash::make('password'), 'name' => 'Nomad Coffee Co.', 'slug' => 'nomad-coffee',
                    'is_complete' => true, 'is_active' => true,
                ]);

                $deals = app(DealService::class);
                $deal = $deals->initiate([
                    'brand_id' => $brand->id, 'talent_id' => $talent->id,
                    'title' => 'Autumn campaign shoot', 'initiated_by' => 'brand',
                    'brief' => 'Two-day lifestyle shoot for the autumn menu launch across three Cairo locations.',
                ], $flow);
                $deals->advance($deal, ['fields' => ['scope' => '2-day lifestyle shoot', 'dates' => 'Oct 12–13', 'budget' => 'EGP 40,000']], 'brand', $brand);
            }
        });
    }
}
