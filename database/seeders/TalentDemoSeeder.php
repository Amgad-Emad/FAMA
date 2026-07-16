<?php

namespace Database\Seeders;

use App\Models\BlockType;
use App\Models\Brand;
use App\Models\BrandCollab;
use App\Models\CompCard;
use App\Models\ContractFlow;
use App\Models\Digital;
use App\Models\Equipment;
use App\Models\LookType;
use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\Review;
use App\Models\Showreel;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\ContractService;
use Database\Seeders\Concerns\GeneratesCoverImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Produces one rich, multi-type demo talent (model + photographer) so later
 * phases have something real to render: two skills, the merged+deduped
 * default blocks seeded into profile_blocks, and populated content tables.
 *
 * Idempotent: the demo talent is rebuilt from scratch each run (children cascade
 * away on delete). Requires TalentTypeSeeder + BlockTypeSeeder to have run.
 */
class TalentDemoSeeder extends Seeder
{
    use GeneratesCoverImages;

    // Mirrors the brand demo login (brand-demo@fama.test) — password: "password".
    private const EMAIL = 'talent-demo@fama.test';

    public function run(): void
    {
        DB::transaction(function (): void {
            // Rebuild from clean state (FK cascade removes children/pivots).
            Talent::withTrashed()->where('email', self::EMAIL)->forceDelete();

            $model = TalentType::where('slug', 'modeling')->firstOrFail();
            $photographer = TalentType::where('slug', 'photography')->firstOrFail();

            $talent = Talent::factory()->create([
                'email' => self::EMAIL,
                'slug' => 'demo-talent',
                'display_name' => 'Layla Hassan',
                'headline' => ['en' => 'Model & Photographer — Cairo', 'ar' => 'عارضة ومصورة — القاهرة'],
                'bio' => ['en' => 'Editorial model and commercial photographer based in Cairo.', 'ar' => 'عارضة تحريرية ومصورة تجارية مقرها القاهرة.'],
                'base_city' => 'Cairo',
                'base_country' => 'Egypt',
                'rate_unit' => 'day',
                'rate_amount' => 8000,
                'rate_currency' => 'EGP',
                'is_published' => true,
            ]);

            // Two skills; model leads the headline.
            $talent->talentTypes()->sync([
                $model->id => ['is_primary' => true, 'position' => 0],
                $photographer->id => ['is_primary' => false, 'position' => 1],
            ]);

            // Avatar image (the cover/hero was removed with the IG-style header — ADR-O).
            $talent->addMedia($this->cover('layla-avatar', 640, 640))->toMediaCollection('avatar');

            // Seed blocks scope-aware (ADR-Q): universal talent-level blocks (hero,
            // reviews, brand collabs) sit in the profile-level section; gated blocks
            // and galleries live in each skill's own tab.
            $this->seedScopedBlocks($talent, [$model, $photographer]);

            // Populate the content tables the demo profile renders. Each skill's tab
            // has its own gallery (ADR-Q), so items are split across the two galleries.
            $modelGallery = $talent->profileBlocks()->where('talent_type_id', $model->id)
                ->whereRelation('blockType', 'key', 'gallery')->first();
            $photographerGallery = $talent->profileBlocks()->where('talent_type_id', $photographer->id)
                ->whereRelation('blockType', 'key', 'gallery')->first();

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
                // First three → the model tab's gallery; the rest → the photographer tab's.
                $block = $i < 3 ? $modelGallery : $photographerGallery;
                $item = PortfolioItem::factory()->for($talent)->create([
                    'block_id' => $block?->id, 'media_type' => 'image', 'caption' => $caption, 'position' => $i,
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
                'talent_type_id' => $photographer->id,
                'title' => ['en' => 'Nomad Coffee — Autumn launch', 'ar' => 'نوماد كوفي — إطلاق الخريف'],
                'client_name' => 'Nomad Coffee Co.', 'year' => 2025, 'position' => 0,
                'role' => ['en' => 'Lead model & stills', 'ar' => 'عارضة رئيسية وتصوير ثابت'],
                'summary' => ['en' => 'A two-day lifestyle shoot introducing the autumn menu across three Cairo locations.', 'ar' => 'تصوير لايف ستايل على مدار يومين لتقديم قائمة الخريف في ثلاثة مواقع بالقاهرة.'],
                'results' => ['Reach' => '2.4M', 'Engagement' => '+38%', 'Assets' => '60+'],
            ]);
            Project::factory()->for($talent)->create([
                'talent_type_id' => $photographer->id,
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

            // A couple of contracts at different steps so the inbox, room and
            // dashboard demo the whole lifecycle for manual QA.
            $flow = ContractFlow::where('slug', 'standard-booking')->first();
            if ($flow !== null) {
                $contracts = app(ContractService::class);
                // Match on the stable `slug` (not email) so BrandDemoSeeder can later
                // change the demo brand's login email without this re-inserting a
                // duplicate slug on a subsequent seed.
                $brand = fn (string $slug, string $name) => Brand::firstOrCreate(
                    ['slug' => $slug],
                    ['email' => $slug.'@fama.test', 'password' => Hash::make('password'), 'name' => $name, 'is_complete' => true, 'is_active' => true],
                );
                $start = fn (Brand $b, string $title, string $brief) => $contracts->initiate(
                    ['brand_id' => $b->id, 'talent_id' => $talent->id, 'title' => $title, 'initiated_by' => 'brand', 'brief' => $brief],
                    $flow,
                );
                $brief = ['fields' => ['scope' => 'Lifestyle shoot', 'dates' => 'Oct 12–13', 'budget' => 'EGP 40,000']];

                // 1) awaiting_talent — brand submitted the brief; talent must quote.
                $nomad = $brand('nomad-coffee', 'Nomad Coffee Co.');
                $d1 = $start($nomad, 'Autumn campaign shoot', 'Two-day lifestyle shoot for the autumn menu launch across three Cairo locations.');
                $contracts->advance($d1, $brief, 'brand', $nomad);

                // 2) awaiting_brand — talent quoted; brand must approve.
                $nefertari = $brand('nefertari-cosmetics', 'Nefertari Cosmetics');
                $d2 = $start($nefertari, 'Spring beauty campaign', 'Studio beauty campaign for the spring colour range.');
                $contracts->advance($d2, $brief, 'brand', $nefertari);
                $contracts->advance($d2, ['fields' => ['amount' => 32000, 'note' => 'Includes light retouching']], 'talent', $talent);

                // 3) completed — the full loop, front to back.
                $gouna = $brand('el-gouna-resorts', 'El Gouna Resorts');
                $d3 = $start($gouna, 'Resort lookbook', 'On-location lookbook across the marina and beach.');
                $contracts->advance($d3, $brief, 'brand', $gouna);
                $contracts->advance($d3, ['fields' => ['amount' => 25000]], 'talent', $talent);
                $contracts->advance($d3, ['note' => 'Approved'], 'brand', $gouna);
                // The deposit is mandatory (never skippable) — pay it.
                $contracts->advance($d3, ['confirmed' => true], 'brand', $gouna);
                $contracts->advance($d3, ['attachments' => ['final-delivery.zip']], 'talent', $talent);
                $contracts->advance($d3, ['note' => 'Beautiful work'], 'brand', $gouna);
            }
        });
    }

    /**
     * Seed a talent's blocks scope-aware (ADR-Q): universal talent-level blocks
     * (hero, reviews, brand collabs — universal + non-repeatable) sit once in the
     * profile-level section; gated blocks and galleries (repeatable) live in each
     * skill's own tab.
     *
     * @param  array<int, \App\Models\TalentType>  $skills
     */
    private function seedScopedBlocks(Talent $talent, array $skills): void
    {
        $allKeys = collect($skills)->flatMap(fn ($s) => $s->default_blocks ?? [])->unique();
        $blockTypes = BlockType::whereIn('key', $allKeys)->get()->keyBy('key');
        $universalOnce = [];
        $universalPos = 0;

        foreach ($skills as $skill) {
            $tabPos = 0;
            foreach (($skill->default_blocks ?? []) as $key) {
                $bt = $blockTypes->get($key);
                if ($bt === null) {
                    continue;
                }

                $universal = $bt->availability === 'universal' && ! $bt->is_repeatable;
                if ($universal && in_array($bt->id, $universalOnce, true)) {
                    continue;
                }
                if ($universal) {
                    $universalOnce[] = $bt->id;
                }

                $talent->profileBlocks()->create([
                    'block_type_id' => $bt->id,
                    'talent_type_id' => $universal ? null : $skill->id,
                    'title' => $bt->getTranslations('name'),
                    'position' => $universal ? $universalPos++ : $tabPos++,
                    'is_visible' => true,
                    'status' => 'visible',
                    'layout' => $bt->default_layout,
                    'settings' => [],
                    'content' => null,
                ]);
            }
        }
    }
}
