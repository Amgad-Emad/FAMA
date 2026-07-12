<?php

use App\Models\Brand;
use App\Models\Deal;
use App\Models\DealFlow;
use App\Models\Talent;
use App\Services\DealService;

beforeEach(fn () => $this->withoutVite());

/**
 * A deal for the talent that is currently on the talent's turn (the quote step).
 */
function roomDeal(Talent $talent): Deal
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

it('renders the inbox and the deal room for the owner', function () {
    $talent = Talent::factory()->create();
    $deal = roomDeal($talent);

    $this->actingAs($talent, 'talent')->get(route('talent.deals'))->assertOk();
    $this->actingAs($talent, 'talent')->get(route('talent.deals.show', $deal))->assertOk();
});

it('lays out the timeline as the central focus with phases + action panel in the side panel', function () {
    $talent = Talent::factory()->create();
    $deal = roomDeal($talent);

    $this->actingAs($talent, 'talent')->get(route('talent.deals.show', $deal))
        ->assertOk()
        // Header (top) carries the back link; the timeline is central (main column),
        // and the current-step action panel + phases stepper sit in the side panel after it.
        ->assertSeeInOrder([
            __('All deals'),
            __('Timeline'),
            __('Message…'),      // the composer lives with the central timeline
            __('Current step'),  // side panel: action panel first…
            __('Phases'),        // …then the phases stepper
        ]);
});

it('returns the thread payload and flags the talent’s turn', function () {
    $talent = Talent::factory()->create();
    $deal = roomDeal($talent);

    $this->actingAs($talent, 'talent')->getJson(route('talent.deals.thread', $deal))
        ->assertOk()
        ->assertJsonPath('data.can_act', true)
        ->assertJsonPath('data.deal.reference', $deal->reference);
});

it('lets the talent advance their step', function () {
    $talent = Talent::factory()->create();
    $deal = roomDeal($talent); // current = quote (talent)

    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.deals.advance', $deal), ['fields' => ['amount' => 3000]])
        ->assertOk();

    expect((float) $deal->fresh()->agreed_amount)->toBe(3000.0);
    expect($deal->fresh()->currentStep->key)->toBe('agreement');
});

it('returns 422 when it is not the talent’s turn', function () {
    $talent = Talent::factory()->create();
    $deal = app(DealService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => $talent->id,
        'title' => 'X',
        'initiated_by' => 'brand',
    ], DealFlow::factory()->standard()->create()); // current = brief (brand)

    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.deals.advance', $deal), ['fields' => ['amount' => 1]])
        ->assertStatus(422);
});

it('posts a free message to the thread', function () {
    $talent = Talent::factory()->create();
    $deal = roomDeal($talent);

    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.deals.message', $deal), ['body' => 'Hi there'])
        ->assertOk();

    expect($deal->messages()->where('type', 'message')->where('body', 'Hi there')->count())->toBe(1);
});

it('forbids acting on another talent’s deal', function () {
    $owner = Talent::factory()->create();
    $deal = roomDeal($owner);
    $intruder = Talent::factory()->create();

    $this->actingAs($intruder, 'talent')->getJson(route('talent.deals.thread', $deal))->assertForbidden();
    $this->actingAs($intruder, 'talent')->postJson(route('talent.deals.advance', $deal), ['fields' => ['amount' => 1]])->assertForbidden();
});

it('filters the inbox by status', function () {
    $talent = Talent::factory()->create();
    roomDeal($talent); // awaiting_talent
    Deal::factory()->create(['talent_id' => $talent->id, 'status' => 'completed']);

    $this->actingAs($talent, 'talent')->getJson(route('talent.deals.data', ['status' => 'awaiting_talent']))
        ->assertOk()->assertJsonPath('meta.pagination.total', 1);
    $this->actingAs($talent, 'talent')->getJson(route('talent.deals.data', ['status' => 'completed']))
        ->assertOk()->assertJsonPath('meta.pagination.total', 1);
});
