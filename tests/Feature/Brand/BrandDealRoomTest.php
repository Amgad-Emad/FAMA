<?php

use App\Models\Brand;
use App\Models\Deal;
use App\Models\DealFlow;
use App\Models\Talent;
use App\Services\DealService;

/** A flow that opens on a brand-actor brief, then auto-completes. */
function brandFirstFlow(): DealFlow
{
    $flow = DealFlow::factory()->create();
    $flow->steps()->createMany([
        ['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['scope']]],
        ['key' => 'done', 'name' => 'Done', 'actor' => 'system', 'step_type' => 'info', 'position' => 1, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
    ]);

    return $flow;
}

function initiateBrandDeal(Brand $brand): Deal
{
    return app(DealService::class)->initiate([
        'brand_id' => $brand->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'Autumn shoot',
        'initiated_by' => 'brand',
    ], brandFirstFlow());
}

it('shows the deal room + thread to the owning brand (awaiting_brand → can act)', function () {
    $brand = Brand::factory()->create();
    $deal = initiateBrandDeal($brand);

    $this->actingAs($brand, 'brand')->get("/brand/deals/{$deal->id}")->assertOk();

    $this->actingAs($brand, 'brand')
        ->getJson("/brand/deals/{$deal->id}/thread")
        ->assertOk()
        ->assertJsonPath('data.can_act', true)
        ->assertJsonPath('data.deal.is_brand_turn', true);
});

it('lets the owning brand submit the brief step', function () {
    $brand = Brand::factory()->create();
    $deal = initiateBrandDeal($brand);

    $this->actingAs($brand, 'brand')
        ->postJson("/brand/deals/{$deal->id}/advance", ['fields' => ['scope' => 'Editorial shoot']])
        ->assertOk();

    expect($deal->fresh()->status->getValue())->toBe('completed');
});

it('rejects a brand acting on another brand’s deal (ownership 403)', function () {
    $owner = Brand::factory()->create();
    $intruder = Brand::factory()->create();
    $deal = initiateBrandDeal($owner);

    $this->actingAs($intruder, 'brand')
        ->postJson("/brand/deals/{$deal->id}/advance", ['fields' => ['scope' => 'x']])
        ->assertForbidden();

    $this->actingAs($intruder, 'brand')
        ->getJson("/brand/deals/{$deal->id}/thread")
        ->assertForbidden();
});

it('lists the brand’s deals in its inbox', function () {
    $brand = Brand::factory()->create();
    initiateBrandDeal($brand);
    initiateBrandDeal(Brand::factory()->create()); // another brand's deal

    $this->actingAs($brand, 'brand')
        ->getJson('/brand/deals/data')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
