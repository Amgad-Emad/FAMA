<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\Concerns\GeneratesCoverImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * One rich demo brand (Nomad Coffee Co.) with the full satellite graph +
 * a campaign, so the brand profile, discovery, and campaign pages have real data
 * to render. Enriches the same brand the deal seeder uses (nomad-coffee), so its
 * deals and profile line up. Idempotent. Requires TalentTypeSeeder.
 */
class BrandDemoSeeder extends Seeder
{
    use GeneratesCoverImages;

    public function run(): void
    {
        DB::transaction(function (): void {
            $brand = Brand::updateOrCreate(['email' => 'nomad-coffee@fama.test'], [
                'password' => Hash::make('password'),
                'name' => 'Nomad Coffee Co.',
                'slug' => 'nomad-coffee',
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
            $need->talentTypes()->sync(TalentType::whereIn('slug', ['model', 'photographer', 'cinematographer'])->pluck('id'));
            $need->projectTypes()->delete();
            foreach (['campaign_video', 'social_content', 'lookbook'] as $projectType) {
                $need->projectTypes()->create(['project_type' => $projectType]);
            }

            // Credibility counters.
            $brand->credibility()->updateOrCreate([], [
                'completed_projects_count' => 18, 'avg_response_time_hours' => 4.5,
                'response_rate_pct' => 96, 'brief_quality_score' => 4.6,
            ]);

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

            // A public campaign with roles + gallery.
            $campaign = $brand->campaigns()->updateOrCreate(['slug' => 'autumn-menu-launch'], [
                'title' => 'Autumn Menu Launch', 'type' => 'campaign',
                'description' => ['en' => 'A lifestyle campaign introducing the autumn menu across three Cairo cafés.', 'ar' => 'حملة نمط حياة لتقديم قائمة الخريف في ثلاثة مقاهٍ بالقاهرة.'],
                'status' => 'open', 'budget_min' => 20000, 'budget_max' => 60000, 'currency' => 'EGP',
                'location_city' => 'Cairo', 'location_country' => 'Egypt', 'is_public' => true, 'positions_count' => 3,
            ]);
            $campaign->clearMediaCollection('cover');
            $campaign->addMedia($this->cover('autumn-cover', 1600, 900))->toMediaCollection('cover');
            $campaign->talentTypes()->sync([
                TalentType::where('slug', 'model')->value('id') => ['quantity' => 1],
                TalentType::where('slug', 'photographer')->value('id') => ['quantity' => 1],
            ]);
            $campaign->gallery->each->delete();
            foreach (['Behind the scenes', 'Menu stills', 'Café interior'] as $i => $caption) {
                $item = $campaign->gallery()->create(['media_type' => 'image', 'caption' => ['en' => $caption, 'ar' => $caption], 'position' => $i]);
                $item->addMedia($this->cover('autumn-g'.$i, 1200, 800))->toMediaCollection('media');
            }
        });
    }
}
