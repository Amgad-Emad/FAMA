<?php

use App\Models\Brand;
use App\Models\Contract;
use App\Models\ContractFlow;
use App\Models\Talent;
use App\Services\ContractService;

/** A flow that opens on a brand-actor brief, then auto-completes. */
function brandFirstFlow(): ContractFlow
{
    $flow = ContractFlow::factory()->create();
    $flow->steps()->createMany([
        ['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['scope']]],
        ['key' => 'done', 'name' => 'Done', 'actor' => 'system', 'step_type' => 'info', 'position' => 1, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
    ]);

    return $flow;
}

function initiateBrandContract(Brand $brand): Contract
{
    return app(ContractService::class)->initiate([
        'brand_id' => $brand->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'Autumn shoot',
        'initiated_by' => 'brand',
    ], brandFirstFlow());
}

it('shows the contract room + thread to the owning brand (awaiting_brand → can act)', function () {
    $brand = Brand::factory()->create();
    $contract = initiateBrandContract($brand);

    $this->actingAs($brand, 'brand')->get("/brand/contracts/{$contract->id}")->assertOk();

    $this->actingAs($brand, 'brand')
        ->getJson("/brand/contracts/{$contract->id}/thread")
        ->assertOk()
        ->assertJsonPath('data.can_act', true)
        ->assertJsonPath('data.contract.is_brand_turn', true);
});

it('lets the owning brand submit the brief step', function () {
    $brand = Brand::factory()->create();
    $contract = initiateBrandContract($brand);

    $this->actingAs($brand, 'brand')
        ->postJson("/brand/contracts/{$contract->id}/advance", ['fields' => ['scope' => 'Editorial shoot']])
        ->assertOk();

    expect($contract->fresh()->status->getValue())->toBe('completed');
});

it('rejects a brand acting on another brand’s contract (ownership 403)', function () {
    $owner = Brand::factory()->create();
    $intruder = Brand::factory()->create();
    $contract = initiateBrandContract($owner);

    $this->actingAs($intruder, 'brand')
        ->postJson("/brand/contracts/{$contract->id}/advance", ['fields' => ['scope' => 'x']])
        ->assertForbidden();

    $this->actingAs($intruder, 'brand')
        ->getJson("/brand/contracts/{$contract->id}/thread")
        ->assertForbidden();
});

it('lists the brand’s contracts in its inbox', function () {
    $brand = Brand::factory()->create();
    initiateBrandContract($brand);
    initiateBrandContract(Brand::factory()->create()); // another brand's contract

    $this->actingAs($brand, 'brand')
        ->getJson('/brand/contracts/data')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('lets the owning brand post a free message in the contract room', function () {
    $brand = Brand::factory()->create();
    $contract = initiateBrandContract($brand);

    $this->actingAs($brand, 'brand')
        ->postJson("/brand/contracts/{$contract->id}/message", ['body' => 'Looking forward to it.'])
        ->assertOk();

    expect($contract->messages()->where('body', 'Looking forward to it.')->where('sender_role', 'brand')->exists())->toBeTrue();
});

it('validates the message body (422 on empty)', function () {
    $brand = Brand::factory()->create();
    $contract = initiateBrandContract($brand);

    $this->actingAs($brand, 'brand')
        ->postJson("/brand/contracts/{$contract->id}/message", ['body' => ''])
        ->assertStatus(422);
});
