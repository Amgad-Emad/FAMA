<?php

use App\Models\Brand;
use App\Models\Contract;
use App\Models\ContractEnquiry;
use App\Models\ContractFlow;
use App\Models\Talent;
use App\Services\ContractService;

function newContract(): Contract
{
    return app(ContractService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'Campaign',
        'initiated_by' => 'brand',
    ], ContractFlow::factory()->standard()->create());
}

it('auto-completes an automatic (auto-confirm payment) step, no human turn', function () {
    // brief (brand) → auto payment (brand, confirmation=auto) → done (system).
    $flow = ContractFlow::factory()->create();
    $flow->steps()->createMany([
        ['key' => 'brief', 'name' => 'Brief', 'actor' => 'brand', 'step_type' => 'form', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['scope']]],
        ['key' => 'deposit', 'name' => 'Auto deposit', 'actor' => 'brand', 'step_type' => 'payment', 'position' => 1, 'is_required' => true, 'is_skippable' => false, 'settings' => ['confirmation' => 'auto', 'percentage' => 50]],
        ['key' => 'done', 'name' => 'Complete', 'actor' => 'system', 'step_type' => 'info', 'position' => 2, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
    ]);

    $svc = app(ContractService::class);
    $contract = $svc->initiate(['brand_id' => Brand::factory()->create()->id, 'talent_id' => Talent::factory()->create()->id, 'title' => 'Auto', 'initiated_by' => 'brand'], $flow);

    // Completing the brief activates the auto-payment, which completes itself,
    // then the system step completes itself → the whole contract completes.
    $contract = $svc->advance($contract, ['fields' => ['scope' => 'x']], 'brand', $contract->brand);

    expect($contract->status::$name)->toBe('completed');
    expect($contract->steps()->where('key', 'deposit')->where('status', 'completed')->exists())->toBeTrue();
});

it('initiates a contract: snapshots the flow and activates the first step', function () {
    $contract = newContract();

    expect($contract->steps()->count())->toBe(7);
    expect($contract->status::$name)->toBe('awaiting_brand'); // brief is a brand step
    expect($contract->currentStep->key)->toBe('brief');
    expect($contract->reference)->toStartWith('FAMA-');
});

it('advances through the full loop to completion', function () {
    $svc = app(ContractService::class);
    $contract = newContract();
    $brand = $contract->brand;
    $talent = $contract->talent;

    $contract = $svc->advance($contract, ['fields' => ['scope' => 'a', 'dates' => 'b', 'budget' => 'c']], 'brand', $brand);
    expect($contract->currentStep->key)->toBe('quote');

    $contract = $svc->advance($contract, ['fields' => ['amount' => 4000]], 'talent', $talent);
    expect($contract->currentStep->key)->toBe('agreement');
    expect((float) $contract->agreed_amount)->toBe(4000.0);

    $contract = $svc->advance($contract, ['note' => 'ok'], 'brand', $brand);   // agreement
    $contract = $svc->advance($contract, ['confirmed' => true], 'brand', $brand); // pay the deposit
    expect($contract->currentStep->key)->toBe('delivery');

    $contract = $svc->advance($contract, ['attachments' => ['final.zip']], 'talent', $talent);
    $contract = $svc->advance($contract, ['note' => 'great'], 'brand', $brand); // sign-off → system complete

    expect($contract->status::$name)->toBe('completed');
    expect($contract->current_step_id)->toBeNull();
    expect($contract->messages()->where('type', 'system_event')->count())->toBeGreaterThan(0);
});

it('loops back on reject, then re-advances', function () {
    $svc = app(ContractService::class);
    $contract = newContract();
    $brand = $contract->brand;
    $talent = $contract->talent;

    $contract = $svc->advance($contract, ['fields' => ['scope' => 'a', 'dates' => 'b', 'budget' => 'c']], 'brand', $brand);
    $contract = $svc->advance($contract, ['fields' => ['amount' => 9000]], 'talent', $talent); // now at agreement (brand)

    $contract = $svc->reject($contract, 'brand', 'too high');
    expect($contract->status::$name)->toBe('awaiting_talent');
    expect($contract->currentStep->key)->toBe('quote'); // sent back to talent

    $contract = $svc->advance($contract, ['fields' => ['amount' => 5000]], 'talent', $talent);
    expect($contract->currentStep->key)->toBe('agreement');
    expect((float) $contract->agreed_amount)->toBe(5000.0);
});

it('cannot skip a non-skippable step', function () {
    $svc = app(ContractService::class);
    $contract = newContract(); // brief is not skippable

    expect(fn () => $svc->skip($contract, 'brand', $contract->brand))->toThrow(InvalidArgumentException::class);
});

it('never lets the brand skip the deposit — it must be paid', function () {
    $svc = app(ContractService::class);
    $contract = newContract();
    $brand = $contract->brand;
    $talent = $contract->talent;

    $contract = $svc->advance($contract, ['fields' => ['scope' => 'a', 'dates' => 'b', 'budget' => 'c']], 'brand', $brand);
    $contract = $svc->advance($contract, ['fields' => ['amount' => 4000]], 'talent', $talent);
    $contract = $svc->advance($contract, ['note' => 'ok'], 'brand', $brand);   // agreement

    expect($contract->currentStep->key)->toBe('payment');
    expect((bool) $contract->currentStep->is_skippable)->toBeFalse();

    // Skipping is refused, and the contract stays parked on the deposit.
    expect(fn () => $svc->skip($contract, 'brand', $brand))->toThrow(InvalidArgumentException::class);
    expect($contract->fresh()->currentStep->key)->toBe('payment');

    // Paying is the only way forward.
    $contract = $svc->advance($contract, ['confirmed' => true], 'brand', $brand);
    expect($contract->currentStep->key)->toBe('delivery');
});

it('refuses to advance out of turn', function () {
    $svc = app(ContractService::class);
    $contract = newContract(); // awaiting_brand (brief)

    expect(fn () => $svc->advance($contract, ['fields' => []], 'talent', $contract->talent))
        ->toThrow(InvalidArgumentException::class);
});

it('converts a pre-auth enquiry into a contract', function () {
    $enquiry = ContractEnquiry::factory()->create();
    $contract = app(ContractService::class)->convertEnquiry($enquiry, Brand::factory()->create(), ContractFlow::factory()->standard()->create());

    expect($enquiry->fresh()->status)->toBe('converted');
    expect($enquiry->fresh()->converted_contract_id)->toBe($contract->id);
    expect($contract->talent_id)->toBe($enquiry->talent_id);
});
