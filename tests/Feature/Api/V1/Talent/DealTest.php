<?php

use App\Models\Brand;
use App\Models\Deal;
use App\Models\DealFlow;
use App\Models\Talent;
use App\Services\DealService;

beforeEach(function () {
    $this->talent = Talent::factory()->create();
    $this->token = $this->talent->createToken('t', ['talent'])->plainTextToken;
});

/**
 * A deal for the talent currently on the talent's turn (the quote step), by
 * advancing the brand's brief on the standard flow.
 */
function apiRoomDeal(Talent $talent): Deal
{
    $svc = app(DealService::class);
    $deal = $svc->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => $talent->id,
        'title' => 'Room deal',
        'initiated_by' => 'brand',
    ], DealFlow::factory()->standard()->create());

    $svc->advance($deal, ['fields' => ['scope' => 'a', 'dates' => 'b', 'budget' => 'c']], 'brand', $deal->brand);

    return $deal->fresh();
}

it('requires a talent token for the inbox', function () {
    $this->getJson('/api/v1/talent/deals')->assertUnauthorized();
});

it('lists and filters the deal inbox, paginated', function () {
    apiRoomDeal($this->talent); // awaiting_talent
    Deal::factory()->create(['talent_id' => $this->talent->id, 'status' => 'completed']);
    Deal::factory()->create(); // someone else's

    api()->withToken($this->token)->getJson('/api/v1/talent/deals')
        ->assertOk()->assertJsonPath('meta.pagination.total', 2); // only mine

    api()->withToken($this->token)->getJson('/api/v1/talent/deals?status=awaiting_talent')
        ->assertOk()->assertJsonPath('meta.pagination.total', 1);
});

it('returns the deal room with steps, messages and whose-turn', function () {
    $deal = apiRoomDeal($this->talent);

    api()->withToken($this->token)->getJson("/api/v1/talent/deals/{$deal->id}")
        ->assertOk()
        ->assertJsonPath('data.can_act', true)
        ->assertJsonPath('data.deal.reference', $deal->reference)
        ->assertJsonStructure(['data' => ['deal', 'steps', 'messages', 'can_act']]);
});

it('lets the talent advance their step through the engine', function () {
    $deal = apiRoomDeal($this->talent); // current = quote (talent)

    api()->withToken($this->token)->postJson("/api/v1/talent/deals/{$deal->id}/advance", ['fields' => ['amount' => 3000]])
        ->assertOk();

    expect((float) $deal->fresh()->agreed_amount)->toBe(3000.0);
    expect($deal->fresh()->currentStep->key)->toBe('agreement');
});

it('422s advancing when it is not the talent’s turn', function () {
    $deal = app(DealService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => $this->talent->id,
        'title' => 'X',
        'initiated_by' => 'brand',
    ], DealFlow::factory()->standard()->create()); // current = brief (brand)

    api()->withToken($this->token)->postJson("/api/v1/talent/deals/{$deal->id}/advance", ['fields' => ['amount' => 1]])
        ->assertStatus(422);
});

it('posts a message to the deal thread', function () {
    $deal = apiRoomDeal($this->talent);

    api()->withToken($this->token)->postJson("/api/v1/talent/deals/{$deal->id}/message", ['body' => 'On it'])
        ->assertOk();

    expect($deal->messages()->where('body', 'On it')->count())->toBe(1);
});

it('validates the message body', function () {
    $deal = apiRoomDeal($this->talent);

    api()->withToken($this->token)->postJson("/api/v1/talent/deals/{$deal->id}/message", [])
        ->assertStatus(422)->assertJsonValidationErrors('body');
});

it('forbids acting on another talent’s deal', function () {
    $foreignDeal = apiRoomDeal(Talent::factory()->create());

    api()->withToken($this->token)->getJson("/api/v1/talent/deals/{$foreignDeal->id}")->assertForbidden();
    api()->withToken($this->token)->postJson("/api/v1/talent/deals/{$foreignDeal->id}/message", ['body' => 'x'])->assertForbidden();
});

it('lists incoming enquiries and forbids foreign ones', function () {
    $mine = $this->talent->dealEnquiries()->create([
        'contact_name' => 'Acme', 'contact_email' => 'hi@acme.test', 'brief' => 'Shoot', 'status' => 'new',
    ]);
    $foreign = Talent::factory()->create()->dealEnquiries()->create([
        'contact_name' => 'X', 'contact_email' => 'x@x.test', 'brief' => 'Y', 'status' => 'new',
    ]);

    api()->withToken($this->token)->getJson('/api/v1/talent/enquiries')
        ->assertOk()->assertJsonPath('meta.pagination.total', 1);
    api()->withToken($this->token)->getJson("/api/v1/talent/enquiries/{$mine->id}")
        ->assertOk()->assertJsonPath('data.contact_name', 'Acme');
    api()->withToken($this->token)->getJson("/api/v1/talent/enquiries/{$foreign->id}")->assertForbidden();
});
