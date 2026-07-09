<?php

use App\Models\Brand;
use App\Models\Deal;
use App\Models\DealFlow;
use App\Models\Talent;
use App\Services\DealService;

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->token = $this->brand->createToken('t', ['brand'])->plainTextToken;
});

/**
 * A deal owned by the brand, freshly initiated so the current step is the brand's
 * brief (the standard flow's first step is a brand-actor form).
 */
function apiBrandDeal(Brand $brand): Deal
{
    return app(DealService::class)->initiate([
        'brand_id' => $brand->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'Brand deal',
        'initiated_by' => 'brand',
    ], DealFlow::factory()->standard()->create());
}

it('requires a brand token for the inbox', function () {
    $this->getJson('/api/v1/brand/deals')->assertUnauthorized();
});

it('lists and filters the deal inbox, paginated', function () {
    apiBrandDeal($this->brand); // awaiting_brand (brief)
    Deal::factory()->create(['brand_id' => $this->brand->id, 'status' => 'completed']);
    Deal::factory()->create(); // another brand's

    api()->withToken($this->token)->getJson('/api/v1/brand/deals')
        ->assertOk()->assertJsonPath('meta.pagination.total', 2);

    api()->withToken($this->token)->getJson('/api/v1/brand/deals?status=awaiting_brand')
        ->assertOk()->assertJsonPath('meta.pagination.total', 1);
});

it('returns the deal room with steps, messages and whose-turn', function () {
    $deal = apiBrandDeal($this->brand);

    api()->withToken($this->token)->getJson("/api/v1/brand/deals/{$deal->id}")
        ->assertOk()
        ->assertJsonPath('data.can_act', true)
        ->assertJsonPath('data.deal.reference', $deal->reference)
        ->assertJsonStructure(['data' => ['deal', 'steps', 'messages', 'can_act']]);
});

it('lets the brand submit the brief (advance) through the engine', function () {
    $deal = apiBrandDeal($this->brand); // current = brief (brand)

    api()->withToken($this->token)->postJson("/api/v1/brand/deals/{$deal->id}/advance", [
        'fields' => ['scope' => 'a', 'dates' => 'b', 'budget' => 'c'],
    ])->assertOk();

    expect($deal->fresh()->currentStep->key)->toBe('quote'); // now the talent's turn
});

it('posts a message and validates the body', function () {
    $deal = apiBrandDeal($this->brand);

    api()->withToken($this->token)->postJson("/api/v1/brand/deals/{$deal->id}/message", ['body' => 'Welcome'])->assertOk();
    expect($deal->messages()->where('body', 'Welcome')->count())->toBe(1);

    api()->withToken($this->token)->postJson("/api/v1/brand/deals/{$deal->id}/message", [])
        ->assertStatus(422)->assertJsonValidationErrors('body');
});

it('forbids acting on another brand’s deal', function () {
    $foreign = apiBrandDeal(Brand::factory()->create());

    api()->withToken($this->token)->getJson("/api/v1/brand/deals/{$foreign->id}")->assertForbidden();
    api()->withToken($this->token)->postJson("/api/v1/brand/deals/{$foreign->id}/message", ['body' => 'x'])->assertForbidden();
});
