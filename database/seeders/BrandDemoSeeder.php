<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\ContractFlow;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\Concerns\GeneratesCoverImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * One rich demo brand (Nomad Coffee Co., slug `nomad-coffee`) with the full
 * satellite graph + campaigns, so the brand profile, discovery, and campaign pages
 * have real data to render. Enriches the SAME `nomad-coffee` brand TalentDemoSeeder
 * creates for the demo talent's contracts (matched on slug), so its contracts and profile
 * line up, and sets the demo brand login: **brand-demo@fama.test / password**.
 * Idempotent. Requires TalentTypeSeeder + runs after TalentDemoSeeder.
 */
class BrandDemoSeeder extends Seeder
{
    use GeneratesCoverImages;

    public function run(): void
    {
        DB::transaction(function (): void {
            // Keyed on the stable `slug` (not email): TalentDemoSeeder creates this
            // same brand first for the demo talent's contracts, so matching on slug
            // enriches THAT brand and sets the demo login email (brand-demo@fama.test)
            // — matching on email would try to insert a duplicate `nomad-coffee` slug.
            $brand = Brand::updateOrCreate(['slug' => 'nomad-coffee'], [
                'email' => 'brand-demo@fama.test',
                'password' => Hash::make('password'),
                'name' => 'Nomad Coffee Co.',
                'description' => ['en' => 'Specialty coffee roasters based in Cairo.', 'ar' => 'محمصة قهوة مختصة مقرها القاهرة.'],
                'industry' => 'food_beverage',
                'brand_stage' => 'growing',
                'base_city' => 'Cairo',
                'base_country' => 'Egypt',
                'geographic_reach' => 'mena',
                'company_size' => 'small',
                'website' => 'https://nomadcoffee.example',
                'is_complete' => true, 'is_active' => true, 'is_verified' => true, 'is_published' => true,
                'status' => 'published',
            ]);

            $brand->clearMediaCollection('logo');
            $brand->clearMediaCollection('cover');
            $brand->addMedia($this->cover('nomad-logo', 600, 600))->toMediaCollection('logo');
            $brand->addMedia($this->cover('nomad-cover', 1600, 900))->toMediaCollection('cover');

            // Aesthetic + promoted mood tags.
            $aesthetic = $brand->aesthetic()->updateOrCreate([], ['brand_references' => 'Kinfolk, Aesop, Monocle — warm minimalism.']);
            $aesthetic->moodTags()->delete();
            foreach (['warm', 'minimal', 'editorial'] as $tag) {
                $aesthetic->moodTags()->create(['tag' => $tag]);
            }

            // The 2–3 brand images.
            $brand->images->each->delete();
            foreach (range(0, 2) as $i) {
                $image = $brand->images()->create(['position' => $i]);
                $image->addMedia($this->cover('nomad-img'.$i, 900, 1200))->toMediaCollection('image');
            }

            // Creative needs + promoted pivots.
            $need = $brand->creativeNeed()->updateOrCreate([], ['project_frequency' => 'monthly', 'budget_tier' => '2000_10000']);
            $need->talentTypes()->sync(TalentType::whereIn('slug', ['modeling', 'photography', 'cinematography'])->pluck('id'));
            $need->projectTypes()->delete();
            foreach (['campaign_video', 'social_content', 'lookbook'] as $projectType) {
                $need->projectTypes()->create(['project_type' => $projectType]);
            }

            // Social handles.
            $brand->socialHandles()->delete();
            $brand->socialHandles()->createMany([
                ['platform' => 'instagram', 'handle' => '@nomadcoffee', 'url' => 'https://instagram.com/nomadcoffee', 'position' => 0],
                ['platform' => 'tiktok', 'handle' => '@nomadcoffee', 'url' => 'https://tiktok.com/@nomadcoffee', 'position' => 1],
            ]);

            // An approved brand review from the demo talent, if present.
            if ($talent = Talent::where('slug', 'demo-talent')->first()) {
                $brand->brandReviews()->updateOrCreate(['talent_id' => $talent->id], [
                    'communication_rating' => 5, 'fairness_rating' => 5, 'creative_respect_rating' => 4,
                    'body' => 'Clear brief, fair terms, and they trusted the creative. Would work with again.',
                    'is_approved' => true, 'status' => 'approved',
                ]);
            }

            // A public project — one role (photography), public budget, + gallery.
            $campaign = $brand->projects()->updateOrCreate(['slug' => 'autumn-menu-launch'], [
                'title' => 'Autumn Menu Launch', 'type' => 'campaign',
                'description' => ['en' => 'A lifestyle campaign introducing the autumn menu across three Cairo cafés.', 'ar' => 'حملة نمط حياة لتقديم قائمة الخريف في ثلاثة مقاهٍ بالقاهرة.'],
                'status' => 'open', 'budget_min' => 20000, 'budget_max' => 60000, 'currency' => 'EGP',
                'location_city' => 'Cairo', 'location_country' => 'Egypt', 'is_public' => true, 'budget_is_public' => true,
                'talent_type_id' => TalentType::where('slug', 'photography')->value('id'),
            ]);
            $campaign->clearMediaCollection('cover');
            $campaign->addMedia($this->cover('autumn-cover', 1600, 900))->toMediaCollection('cover');
            $campaign->gallery->each->delete();
            foreach (['Behind the scenes', 'Menu stills', 'Café interior'] as $i => $caption) {
                $item = $campaign->gallery()->create(['media_type' => 'image', 'caption' => ['en' => $caption, 'ar' => $caption], 'position' => $i]);
                $item->addMedia($this->cover('autumn-g'.$i, 1200, 800))->toMediaCollection('media');
            }

            // A second project — a completed showcase (one role, private budget).
            $showcase = $brand->projects()->updateOrCreate(['slug' => 'ramadan-lantern-series'], [
                'title' => 'Ramadan Lantern Series', 'type' => 'shoot',
                'description' => ['en' => 'A completed editorial series shot across old Cairo.', 'ar' => 'سلسلة تحريرية مكتملة صُوّرت في القاهرة القديمة.'],
                'status' => 'completed', 'budget_min' => 15000, 'budget_max' => 35000, 'currency' => 'EGP',
                'location_city' => 'Cairo', 'location_country' => 'Egypt', 'is_public' => true, 'budget_is_public' => false,
                'talent_type_id' => TalentType::where('slug', 'photography')->value('id'),
            ]);
            $showcase->clearMediaCollection('cover');
            $showcase->addMedia($this->cover('ramadan-cover', 1600, 900))->toMediaCollection('cover');

            // A COMPLETED contract running under the open campaign (contracts.brand_project_id), so
            // the campaign workspace and contracts inbox line up. Created THROUGH the contract
            // engine (not raw) so it has real snapshotted steps + a full message/step
            // history — the contract room renders the counterparty, brief, and stepper.
            $flow = ContractFlow::where('slug', 'standard-booking')->first() ?? ContractFlow::query()->first();
            if ($flow !== null && isset($talent) && ! $brand->contracts()->where('reference', 'NOMAD-AUTUMN-01')->exists()) {
                $contracts = app(\App\Services\ContractService::class);

                $contract = $contracts->initiate([
                    'brand_id' => $brand->id, 'talent_id' => $talent->id,
                    'title' => 'Autumn Menu — lead photographer', 'initiated_by' => 'brand',
                    'brief' => 'Two-day lifestyle shoot for the autumn menu launch across three Cairo cafés.',
                ], $flow);

                // Walk the whole loop to completed (mirrors the standard-booking flow).
                $brief = ['fields' => ['scope' => 'Autumn menu lifestyle shoot', 'dates' => 'Oct 12–14', 'budget' => 'EGP 30,000']];
                $contracts->advance($contract, $brief, 'brand', $brand);
                $contracts->advance($contract, ['fields' => ['amount' => 28000, 'note' => 'Includes usage rights']], 'talent', $talent);
                $contracts->advance($contract, ['note' => 'Approved — excited to start'], 'brand', $brand);
                // The deposit is mandatory (never skippable) — pay it.
                $contracts->advance($contract, ['confirmed' => true, 'reference' => 'DEP-NOMAD-01'], 'brand', $brand);
                $contracts->advance($contract, ['attachments' => ['autumn-final-delivery.zip']], 'talent', $talent);
                $contracts->advance($contract, ['note' => 'Beautiful work, thank you'], 'brand', $brand);

                // Stamp the demo reference + campaign link (the engine assigns its own reference).
                $contract->forceFill(['reference' => 'NOMAD-AUTUMN-01', 'brand_project_id' => $campaign->id])->save();
            }

            // Credibility counters — set LAST: completing the contract above fires the
            // "recalc credibility" side effect, so these curated demo numbers win.
            $brand->credibility()->updateOrCreate([], [
                'completed_projects_count' => 18, 'avg_response_time_hours' => 4.5,
                'response_rate_pct' => 96, 'brief_quality_score' => 4.6,
            ]);
        });
    }
}
