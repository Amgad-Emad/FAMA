<?php

use App\Models\Brand;
use App\Models\Deal;
use App\Models\DealFlow;
use App\Models\Talent;
use App\Services\DealService;

/**
 * A brand deal freshly initiated (current step = brand's brief), plus the talent
 * who is the counterparty.
 */
function notifyDeal(Talent $talent): Deal
{
    return app(DealService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => $talent->id,
        'title' => 'Shoot',
        'initiated_by' => 'brand',
    ], DealFlow::factory()->standard()->create());
}

it('requires a token', function () {
    $this->getJson('/api/v1/notifications')->assertUnauthorized();
});

it('notifies the talent when the brand advances the turn to them', function () {
    $talent = Talent::factory()->create();
    $deal = notifyDeal($talent);

    // Brand submits the brief → the turn moves to the talent, who is notified.
    app(DealService::class)->advance($deal, ['fields' => ['scope' => 'a', 'dates' => 'b', 'budget' => 'c']], 'brand', $deal->brand);

    $token = $talent->createToken('t', ['talent'])->plainTextToken;

    api()->withToken($token)->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonPath('meta.pagination.total', 1)
        ->assertJsonPath('data.0.type', 'deal.turn')
        ->assertJsonPath('data.0.data.deal_id', $deal->id)
        ->assertJsonPath('data.0.is_read', false);
});

it('notifies the counterparty on a new message', function () {
    $talent = Talent::factory()->create();
    $deal = notifyDeal($talent);

    app(DealService::class)->postMessage($deal, 'brand', $deal->brand, 'Hello there');

    $token = $talent->createToken('t', ['talent'])->plainTextToken;

    api()->withToken($token)->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonPath('meta.pagination.total', 1)
        ->assertJsonPath('data.0.type', 'deal.message');
});

it('reports the unread count and marks read / all read', function () {
    $talent = Talent::factory()->create();
    $deal = notifyDeal($talent);
    app(DealService::class)->advance($deal, ['fields' => ['scope' => 'a', 'dates' => 'b', 'budget' => 'c']], 'brand', $deal->brand);
    app(DealService::class)->postMessage($deal, 'brand', $deal->brand, 'Two');

    $token = $talent->createToken('t', ['talent'])->plainTextToken;

    api()->withToken($token)->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.unread', 2);

    $id = api()->withToken($token)->getJson('/api/v1/notifications')->json('data.0.id');
    api()->withToken($token)->postJson("/api/v1/notifications/{$id}/read")->assertOk();
    api()->withToken($token)->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.unread', 1);

    api()->withToken($token)->postJson('/api/v1/notifications/read-all')->assertOk()->assertJsonPath('data.unread', 0);
    api()->withToken($token)->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.unread', 0);
});

it('scopes notifications to the authenticated entity', function () {
    $talent = Talent::factory()->create();
    $deal = notifyDeal($talent);
    app(DealService::class)->advance($deal, ['fields' => ['scope' => 'a', 'dates' => 'b', 'budget' => 'c']], 'brand', $deal->brand);

    // A different talent sees nothing.
    $otherToken = Talent::factory()->create()->createToken('t', ['talent'])->plainTextToken;

    api()->withToken($otherToken)->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('meta.pagination.total', 0);
});
