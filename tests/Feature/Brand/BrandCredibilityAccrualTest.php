<?php

use App\Models\Brand;
use App\Models\DealFlow;
use App\Models\Talent;
use App\Services\DealService;

/** A minimal flow: brand brief → system done (auto-completes the deal). */
function tinyFlow(): DealFlow
{
    $flow = DealFlow::factory()->create();
    $flow->steps()->createMany([
        ['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['scope']]],
        ['key' => 'done', 'name' => 'Done', 'actor' => 'system', 'step_type' => 'info', 'position' => 1, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
    ]);

    return $flow;
}

function completeDealFor(Brand $brand): void
{
    $svc = app(DealService::class);
    $deal = $svc->initiate([
        'brand_id' => $brand->id, 'talent_id' => Talent::factory()->create()->id,
        'title' => 'Job', 'initiated_by' => 'brand',
    ], tinyFlow());
    $svc->advance($deal, ['fields' => ['scope' => 'x']], 'brand', $brand);
}

it('accrues brand credibility when a deal completes (event → listener)', function () {
    $brand = Brand::factory()->create();

    completeDealFor($brand);

    expect($brand->credibility()->first()->completed_projects_count)->toBe(1);
    expect((int) $brand->credibility()->first()->response_rate_pct)->toBeGreaterThan(0);
});

it('keeps completed_projects_count monotonic across completions', function () {
    $brand = Brand::factory()->create();

    completeDealFor($brand);
    completeDealFor($brand);

    expect($brand->credibility()->first()->completed_projects_count)->toBe(2);
});
