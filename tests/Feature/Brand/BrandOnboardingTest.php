<?php

use App\Models\Brand;
use App\Models\TalentType;
use App\Services\BrandOnboardingService;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed(TalentTypeSeeder::class));

it('walks the 6-step wizard and flips is_complete', function () {
    $brand = Brand::factory()->create(['status' => 'registered', 'is_complete' => false, 'is_published' => false]);
    $service = app(BrandOnboardingService::class);

    // 1 — identity → registered → onboarding
    $service->identity($brand, ['name' => 'Nomad Coffee', 'description' => ['en' => 'Coffee'], 'industry' => 'food_beverage', 'brand_stage' => 'growing']);
    expect($brand->fresh()->status->getValue())->toBe('onboarding');

    // 2 — location/reach
    $service->location($brand, ['base_city' => 'Cairo', 'base_country' => 'Egypt', 'geographic_reach' => 'mena']);
    expect($brand->fresh()->base_city)->toBe('Cairo');

    // 3 — creative needs (pivots)
    $typeIds = TalentType::whereIn('slug', ['modeling', 'photography'])->pluck('id')->all();
    $need = $service->creativeNeeds($brand, ['talent_type_ids' => $typeIds, 'project_types' => ['campaign_video', 'lookbook'], 'project_frequency' => 'monthly']);
    expect($need->talentTypes()->count())->toBe(2);
    expect($need->projectTypes()->count())->toBe(2);

    // 4 — aesthetic + mood tags
    $aesthetic = $service->aesthetic($brand, ['mood_tags' => ['warm', 'minimal'], 'brand_references' => 'Kinfolk']);
    expect($aesthetic->moodTags()->count())->toBe(2);

    // 5 — budget
    $service->budget($brand, '2000_10000');
    expect($brand->creativeNeed()->first()->budget_tier)->toBe('2000_10000');

    // 6 — finish → onboarding → complete, is_complete synced
    $done = $service->complete($brand);
    expect($done->status->getValue())->toBe('complete');
    expect((bool) $done->is_complete)->toBeTrue();
});

it('is idempotent per step (re-running does not duplicate pivots or re-transition)', function () {
    $brand = Brand::factory()->create(['status' => 'registered', 'is_complete' => false]);
    $service = app(BrandOnboardingService::class);
    $typeIds = TalentType::whereIn('slug', ['modeling'])->pluck('id')->all();

    $service->identity($brand, ['name' => 'X']);
    $service->identity($brand, ['name' => 'X again']); // still onboarding, no error
    $service->creativeNeeds($brand, ['talent_type_ids' => $typeIds, 'project_types' => ['editorial']]);
    $need = $service->creativeNeeds($brand, ['talent_type_ids' => $typeIds, 'project_types' => ['editorial']]);

    expect($brand->fresh()->status->getValue())->toBe('onboarding');
    expect($need->talentTypes()->count())->toBe(1);
    expect($need->projectTypes()->count())->toBe(1);
});
