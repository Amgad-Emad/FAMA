<?php

namespace Database\Seeders;

use App\Models\BlockType;
use App\Models\BrandCollab;
use App\Models\CaseStudy;
use App\Models\CompCard;
use App\Models\Digital;
use App\Models\Equipment;
use App\Models\LookType;
use App\Models\PortfolioItem;
use App\Models\Review;
use App\Models\Service;
use App\Models\Showreel;
use App\Models\Talent;
use App\Models\TalentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

            PortfolioItem::factory()->count(6)->for($talent)->create(['block_id' => $galleryBlock?->id]);
            CompCard::factory()->for($talent)->create();               // model
            LookType::factory()->count(4)->for($talent)->create();     // model
            Digital::factory()->count(5)->for($talent)->create();      // model
            Equipment::factory()->count(6)->for($talent)->create();    // photographer
            Showreel::factory()->count(2)->for($talent)->create();     // photographer
            CaseStudy::factory()->count(2)->for($talent)->create();
            BrandCollab::factory()->count(4)->for($talent)->create();
            Service::factory()->count(3)->for($talent)->create();
            Review::factory()->count(3)->for($talent)->create();       // approved
            Review::factory()->pending()->for($talent)->create();      // in moderation
        });
    }
}
