<?php

use App\Models\Brand;
use App\Models\ContractFlow;
use App\Models\Talent;
use App\Services\ContractService;

/** A minimal flow: brand brief → system done (auto-completes the contract). */
function tinyFlow(): ContractFlow
{
    $flow = ContractFlow::factory()->create();
    $flow->steps()->createMany([
        ['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['scope']]],
        ['key' => 'done', 'name' => 'Done', 'actor' => 'system', 'step_type' => 'info', 'position' => 1, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
    ]);

    return $flow;
}

function completeContractFor(Brand $brand): void
{
    $svc = app(ContractService::class);
    $contract = $svc->initiate([
        'brand_id' => $brand->id, 'talent_id' => Talent::factory()->create()->id,
        'title' => 'Job', 'initiated_by' => 'brand',
    ], tinyFlow());
    $svc->advance($contract, ['fields' => ['scope' => 'x']], 'brand', $brand);
}

it('accrues brand credibility when a contract completes (event → listener)', function () {
    $brand = Brand::factory()->create();

    completeContractFor($brand);

    expect($brand->credibility()->first()->completed_projects_count)->toBe(1);
    expect((int) $brand->credibility()->first()->response_rate_pct)->toBeGreaterThan(0);
});

it('keeps completed_projects_count monotonic across completions', function () {
    $brand = Brand::factory()->create();

    completeContractFor($brand);
    completeContractFor($brand);

    expect($brand->credibility()->first()->completed_projects_count)->toBe(2);
});
