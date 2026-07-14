<?php

use App\Models\Brand;
use App\Models\BrandAesthetic;
use App\Models\BrandCreativeNeed;
use App\Models\BrandCredibility;
use App\Models\BrandReview;
use App\Models\BrandSignal;
use App\Models\BrandSocialHandle;
use App\Models\Campaign;
use App\Models\CampaignMedia;
use App\Models\TalentType;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

it('wires the brand satellite relationships', function () {
    $brand = Brand::factory()->create();
    $brand->aesthetic()->save(BrandAesthetic::factory()->make());
    $brand->creativeNeed()->save(BrandCreativeNeed::factory()->make());
    $brand->credibility()->save(BrandCredibility::factory()->make());
    $brand->socialHandles()->save(BrandSocialHandle::factory()->make());
    $brand->signals()->save(BrandSignal::factory()->make(['talent_id' => null]));
    BrandReview::factory()->for($brand)->create();
    Campaign::factory()->for($brand)->create();

    $brand->refresh();
    expect($brand->aesthetic)->toBeInstanceOf(BrandAesthetic::class);
    expect($brand->creativeNeed)->toBeInstanceOf(BrandCreativeNeed::class);
    expect($brand->credibility)->toBeInstanceOf(BrandCredibility::class);
    expect($brand->socialHandles)->toHaveCount(1);
    expect($brand->signals)->toHaveCount(1);
    expect($brand->brandReviews)->toHaveCount(1);
    expect($brand->campaigns)->toHaveCount(1);
});

it('translates the brand description', function () {
    $brand = Brand::factory()->create(['description' => ['en' => 'Specialty coffee', 'ar' => 'قهوة مختصة']]);

    expect($brand->getTranslation('description', 'en'))->toBe('Specialty coffee');
    expect($brand->getTranslation('description', 'ar'))->toBe('قهوة مختصة');
});

it('resolves logo + cover through medialibrary', function () {
    Storage::fake('public');
    $brand = Brand::factory()->create();

    expect($brand->logo_url)->toBeNull();
    $brand->addMedia(UploadedFile::fake()->image('logo.jpg', 400, 400))->toMediaCollection('logo');

    expect($brand->fresh()->logo_url)->not->toBeNull();
});

it('promotes mood tags to a queryable pivot (ADR-6)', function () {
    $brand = Brand::factory()->create();
    $brand->aesthetic()->save(BrandAesthetic::factory()->make());
    $brand->aesthetic->moodTags()->createMany([['tag' => 'editorial'], ['tag' => 'minimal']]);

    expect($brand->aesthetic->moodTags->pluck('tag')->all())->toBe(['editorial', 'minimal']);
    // discovery-shaped query: brands with an editorial mood
    $found = Brand::whereHas('aesthetic.moodTags', fn ($q) => $q->where('tag', 'editorial'))->pluck('id');
    expect($found)->toContain($brand->id);
});

it('promotes creative-need talent types + project types to pivots (ADR-6)', function () {
    $this->seed(TalentTypeSeeder::class);
    $brand = Brand::factory()->create();
    $need = $brand->creativeNeed()->save(BrandCreativeNeed::factory()->make());
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();

    $need->talentTypes()->attach($photographer->id);
    $need->projectTypes()->create(['project_type' => 'campaign_video']);

    // "all brands needing photographers"
    $found = Brand::whereHas('creativeNeed.talentTypes', fn ($q) => $q->where('slug', 'photography'))->pluck('id');
    expect($found)->toContain($brand->id);
    expect($need->projectTypes->pluck('project_type')->all())->toBe(['campaign_video']);
});

it('averages the three brand-review sub-ratings and defaults to pending', function () {
    $review = BrandReview::factory()->pending()->create([
        'communication_rating' => 5, 'fairness_rating' => 4, 'creative_respect_rating' => 3,
    ]);

    expect($review->average_rating)->toBe(4.0);
    expect((bool) $review->is_approved)->toBeFalse();
    expect($review->status->getValue())->toBe('pending');
});

it('links a campaign to its roles (with quantity) and gallery', function () {
    $this->seed(TalentTypeSeeder::class);
    $campaign = Campaign::factory()->open()->create();
    $model = TalentType::where('slug', 'modeling')->firstOrFail();

    $campaign->talentTypes()->attach($model->id, ['quantity' => 2]);
    $campaign->gallery()->save(CampaignMedia::factory()->make());

    expect($campaign->talentTypes)->toHaveCount(1);
    expect((int) $campaign->talentTypes->first()->pivot->quantity)->toBe(2);
    expect($campaign->gallery)->toHaveCount(1);
    expect($campaign->status->getValue())->toBe('open');
});

it('keeps brand_signals append-only (no updated_at column)', function () {
    expect(Schema::hasColumn('brand_signals', 'updated_at'))->toBeFalse();
    expect(BrandSignal::UPDATED_AT)->toBeNull();
});
