<?php

use App\Models\Brand;
use App\Models\BrandProject;
use App\Models\TalentType;
use App\Services\BrandProjectService;
use Database\Seeders\TalentTypeSeeder;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

beforeEach(fn () => $this->seed(TalentTypeSeeder::class));

it('creates a project with a single role and walks draft → open → in_progress → completed', function () {
    $brand = Brand::factory()->create();
    $service = app(BrandProjectService::class);
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();

    $campaign = $service->create($brand, [
        'title' => 'Autumn Launch', 'type' => 'campaign', 'budget_min' => 10000, 'budget_max' => 40000,
        'is_public' => true, 'budget_is_public' => true, 'talent_type_id' => $photographer->id,
    ]);

    expect($campaign->status->getValue())->toBe('draft');
    expect($campaign->talent_type_id)->toBe($photographer->id);
    expect($campaign->talentType->slug)->toBe('photography');

    $service->open($campaign);
    $service->start($campaign);
    $done = $service->complete($campaign);

    expect($done->status->getValue())->toBe('completed');
    expect(BrandProject::showcase()->pluck('id'))->toContain($campaign->id);
});

it('cancels from an active state and toggles is_public independently of status', function () {
    $brand = Brand::factory()->create();
    $service = app(BrandProjectService::class);
    $campaign = $service->create($brand, ['title' => 'X', 'is_public' => false]);

    $service->setPublic($campaign, true);
    expect((bool) $campaign->fresh()->is_public)->toBeTrue();
    expect($campaign->fresh()->status->getValue())->toBe('draft'); // status unchanged

    $cancelled = $service->cancel($campaign);
    expect($cancelled->status->getValue())->toBe('cancelled');
});

it('rejects an illegal campaign transition (draft → completed)', function () {
    $brand = Brand::factory()->create();
    $campaign = app(BrandProjectService::class)->create($brand, ['title' => 'X']);

    expect(fn () => app(BrandProjectService::class)->complete($campaign))->toThrow(CouldNotPerformTransition::class);
});
