<?php

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\TalentType;
use App\Services\CampaignService;
use Database\Seeders\TalentTypeSeeder;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

beforeEach(fn () => $this->seed(TalentTypeSeeder::class));

it('creates a campaign with roles and walks draft → open → in_progress → completed', function () {
    $brand = Brand::factory()->create();
    $service = app(CampaignService::class);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();

    $campaign = $service->create($brand, [
        'title' => 'Autumn Launch', 'type' => 'campaign', 'budget_min' => 10000, 'budget_max' => 40000,
        'is_public' => true, 'roles' => [$model->id => 1, $photographer->id => 2],
    ]);

    expect($campaign->status->getValue())->toBe('draft');
    expect($campaign->talentTypes()->count())->toBe(2);
    expect((int) $campaign->talentTypes()->where('talent_type_id', $photographer->id)->first()->pivot->quantity)->toBe(2);

    $service->open($campaign);
    $service->start($campaign);
    $done = $service->complete($campaign);

    expect($done->status->getValue())->toBe('completed');
    expect(Campaign::showcase()->pluck('id'))->toContain($campaign->id);
});

it('cancels from an active state and toggles is_public independently of status', function () {
    $brand = Brand::factory()->create();
    $service = app(CampaignService::class);
    $campaign = $service->create($brand, ['title' => 'X', 'is_public' => false]);

    $service->setPublic($campaign, true);
    expect((bool) $campaign->fresh()->is_public)->toBeTrue();
    expect($campaign->fresh()->status->getValue())->toBe('draft'); // status unchanged

    $cancelled = $service->cancel($campaign);
    expect($cancelled->status->getValue())->toBe('cancelled');
});

it('rejects an illegal campaign transition (draft → completed)', function () {
    $brand = Brand::factory()->create();
    $campaign = app(CampaignService::class)->create($brand, ['title' => 'X']);

    expect(fn () => app(CampaignService::class)->complete($campaign))->toThrow(CouldNotPerformTransition::class);
});
