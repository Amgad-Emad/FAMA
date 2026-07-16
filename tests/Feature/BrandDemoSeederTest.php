<?php

use App\Models\Brand;
use App\Models\Contract;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\BrandDemoSeeder;
use Database\Seeders\ContractFlowSeeder;
use Database\Seeders\TalentDemoSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(function () {
    // Same prerequisite chain as DatabaseSeeder: catalogs + flows, then the demos.
    $this->seed(TalentTypeSeeder::class);
    $this->seed(BlockTypeSeeder::class);
    $this->seed(ContractFlowSeeder::class);
    $this->seed(TalentDemoSeeder::class); // provides demo-talent
    $this->seed(BrandDemoSeeder::class);
});

it('seeds a fully onboarded, published demo brand with the satellite graph', function () {
    $brand = Brand::where('slug', 'nomad-coffee')->firstOrFail();

    expect((bool) $brand->is_published)->toBeTrue();
    expect((bool) $brand->is_complete)->toBeTrue();
    expect($brand->status->getValue())->toBe('published');
    expect($brand->logo_url)->not->toBeNull();
    expect($brand->aesthetic->moodTags()->count())->toBe(3);
    expect($brand->creativeNeed->talentTypes()->count())->toBe(3);
    expect($brand->creativeNeed->projectTypes()->count())->toBe(3);
    expect($brand->images()->count())->toBe(3);
    expect($brand->socialHandles()->count())->toBe(2);
    expect($brand->credibility->completed_projects_count)->toBe(18);
    expect($brand->brandReviews()->where('is_approved', true)->count())->toBe(1);
});

it('seeds two campaigns at different statuses and a contract under a campaign', function () {
    $brand = Brand::where('slug', 'nomad-coffee')->firstOrFail();

    expect($brand->projects()->count())->toBe(2);
    $statuses = $brand->projects()->get()->pluck('status')->map(fn ($state) => $state->getValue())->sort()->values()->all();
    expect($statuses)->toBe(['completed', 'open']);

    $contract = Contract::whereNotNull('brand_project_id')->where('brand_id', $brand->id)->first();
    expect($contract)->not->toBeNull();
    expect($contract->project->brand_id)->toBe($brand->id);
});

it('is idempotent (re-running does not duplicate the graph)', function () {
    $this->seed(BrandDemoSeeder::class);

    $brand = Brand::where('slug', 'nomad-coffee')->firstOrFail();
    expect(Brand::where('slug', 'nomad-coffee')->count())->toBe(1);
    expect($brand->projects()->count())->toBe(2);
    expect($brand->images()->count())->toBe(3);
});
