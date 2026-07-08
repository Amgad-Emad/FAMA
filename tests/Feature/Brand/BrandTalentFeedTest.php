<?php

use App\Models\Brand;
use App\Models\Talent;
use App\Models\TalentType;
use App\Queries\BrandTalentFeed;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed(TalentTypeSeeder::class));

it('personalises the feed by the brand creative needs + records a browse signal', function () {
    $brand = Brand::factory()->create(['geographic_reach' => 'mena']);
    $need = $brand->creativeNeed()->create(['project_frequency' => 'monthly']);
    $photographer = TalentType::where('slug', 'photographer')->firstOrFail();
    $model = TalentType::where('slug', 'model')->firstOrFail();
    $need->talentTypes()->attach($photographer->id);

    $wanted = Talent::factory()->create();
    $wanted->talentTypes()->attach($photographer->id, ['is_primary' => true, 'position' => 0]);
    $other = Talent::factory()->create();
    $other->talentTypes()->attach($model->id, ['is_primary' => true, 'position' => 0]);

    $feed = app(BrandTalentFeed::class)->paginate($brand);

    expect($feed->total())->toBe(1);
    expect($feed->first()->id)->toBe($wanted->id);
    expect($brand->signals()->where('action_type', 'view')->count())->toBe(1);
});

it('restricts to the brand city when geographic_reach is same_city', function () {
    $brand = Brand::factory()->create(['geographic_reach' => 'same_city', 'base_city' => 'Cairo']);
    $brand->creativeNeed()->create([]); // no type filter → all published
    Talent::factory()->create(['base_city' => 'Cairo']);
    Talent::factory()->create(['base_city' => 'Dubai']);

    $feed = app(BrandTalentFeed::class)->paginate($brand, recordBrowse: false);

    expect($feed->total())->toBe(1);
});

it('paginates the feed and eager-loads (no lazy-load errors)', function () {
    $brand = Brand::factory()->create(['geographic_reach' => 'international']);
    $brand->creativeNeed()->create([]);
    Talent::factory()->count(15)->create();

    $feed = app(BrandTalentFeed::class)->paginate($brand, 12, false);

    expect($feed->perPage())->toBe(12);
    expect($feed->total())->toBe(15);
    // eager-loaded relation is accessible without a lazy-load violation
    expect($feed->first()->relationLoaded('talentTypes'))->toBeTrue();
});
