<?php

use App\Models\Brand;
use App\Models\Deal;
use App\Models\DealEnquiry;
use App\Models\DealFlow;
use App\Models\Talent;
use App\Services\DealService;

function newDeal(): Deal
{
    return app(DealService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'Campaign',
        'initiated_by' => 'brand',
    ], DealFlow::factory()->standard()->create());
}

it('initiates a deal: snapshots the flow and activates the first step', function () {
    $deal = newDeal();

    expect($deal->steps()->count())->toBe(7);
    expect($deal->status::$name)->toBe('awaiting_brand'); // brief is a brand step
    expect($deal->currentStep->key)->toBe('brief');
    expect($deal->reference)->toStartWith('FAMA-');
});

it('advances through the full loop to completion', function () {
    $svc = app(DealService::class);
    $deal = newDeal();
    $brand = $deal->brand;
    $talent = $deal->talent;

    $deal = $svc->advance($deal, ['fields' => ['scope' => 'a', 'dates' => 'b', 'budget' => 'c']], 'brand', $brand);
    expect($deal->currentStep->key)->toBe('quote');

    $deal = $svc->advance($deal, ['fields' => ['amount' => 4000]], 'talent', $talent);
    expect($deal->currentStep->key)->toBe('agreement');
    expect((float) $deal->agreed_amount)->toBe(4000.0);

    $deal = $svc->advance($deal, ['note' => 'ok'], 'brand', $brand);   // agreement
    $deal = $svc->skip($deal, 'brand', $brand);                        // skip payment
    expect($deal->currentStep->key)->toBe('delivery');

    $deal = $svc->advance($deal, ['attachments' => ['final.zip']], 'talent', $talent);
    $deal = $svc->advance($deal, ['note' => 'great'], 'brand', $brand); // sign-off → system complete

    expect($deal->status::$name)->toBe('completed');
    expect($deal->current_step_id)->toBeNull();
    expect($deal->messages()->where('type', 'system_event')->count())->toBeGreaterThan(0);
});

it('loops back on reject, then re-advances', function () {
    $svc = app(DealService::class);
    $deal = newDeal();
    $brand = $deal->brand;
    $talent = $deal->talent;

    $deal = $svc->advance($deal, ['fields' => ['scope' => 'a', 'dates' => 'b', 'budget' => 'c']], 'brand', $brand);
    $deal = $svc->advance($deal, ['fields' => ['amount' => 9000]], 'talent', $talent); // now at agreement (brand)

    $deal = $svc->reject($deal, 'brand', 'too high');
    expect($deal->status::$name)->toBe('awaiting_talent');
    expect($deal->currentStep->key)->toBe('quote'); // sent back to talent

    $deal = $svc->advance($deal, ['fields' => ['amount' => 5000]], 'talent', $talent);
    expect($deal->currentStep->key)->toBe('agreement');
    expect((float) $deal->agreed_amount)->toBe(5000.0);
});

it('cannot skip a non-skippable step', function () {
    $svc = app(DealService::class);
    $deal = newDeal(); // brief is not skippable

    expect(fn () => $svc->skip($deal, 'brand', $deal->brand))->toThrow(InvalidArgumentException::class);
});

it('refuses to advance out of turn', function () {
    $svc = app(DealService::class);
    $deal = newDeal(); // awaiting_brand (brief)

    expect(fn () => $svc->advance($deal, ['fields' => []], 'talent', $deal->talent))
        ->toThrow(InvalidArgumentException::class);
});

it('converts a pre-auth enquiry into a deal', function () {
    $enquiry = DealEnquiry::factory()->create();
    $deal = app(DealService::class)->convertEnquiry($enquiry, Brand::factory()->create(), DealFlow::factory()->standard()->create());

    expect($enquiry->fresh()->status)->toBe('converted');
    expect($enquiry->fresh()->converted_deal_id)->toBe($deal->id);
    expect($deal->talent_id)->toBe($enquiry->talent_id);
});
