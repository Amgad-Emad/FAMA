<?php

use App\Models\Brand;
use App\Models\Contract;
use App\Models\ContractFlow;
use App\Models\Talent;
use App\Services\ContractService;

beforeEach(fn () => $this->withoutVite());

/**
 * A contract for the talent that is currently on the talent's turn (the quote step).
 */
function roomContract(Talent $talent): Contract
{
    $svc = app(ContractService::class);
    $contract = $svc->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => $talent->id,
        'title' => 'Room contract',
        'initiated_by' => 'brand',
    ], ContractFlow::factory()->standard()->create());

    $svc->advance($contract, ['fields' => ['scope' => 'a', 'dates' => 'b', 'budget' => 'c']], 'brand', $contract->brand);

    return $contract->fresh();
}

it('renders the inbox and the contract room for the owner', function () {
    $talent = Talent::factory()->create();
    $contract = roomContract($talent);

    $this->actingAs($talent, 'talent')->get(route('talent.contracts'))->assertOk();
    $this->actingAs($talent, 'talent')->get(route('talent.contracts.show', $contract))->assertOk();
});

it('lays out the timeline as the central focus with phases + action panel in the side panel', function () {
    $talent = Talent::factory()->create();
    $contract = roomContract($talent);

    $this->actingAs($talent, 'talent')->get(route('talent.contracts.show', $contract))
        ->assertOk()
        // Header (top) carries the back link; the timeline is central (main column),
        // and the current-step action panel + phases stepper sit in the side panel after it.
        ->assertSeeInOrder([
            __('All contracts'),
            __('Timeline'),
            __('Message…'),      // the composer lives with the central timeline
            __('Current step'),  // side panel: action panel first…
            __('Phases'),        // …then the phases stepper
        ]);
});

it('returns the thread payload and flags the talent’s turn', function () {
    $talent = Talent::factory()->create();
    $contract = roomContract($talent);

    $this->actingAs($talent, 'talent')->getJson(route('talent.contracts.thread', $contract))
        ->assertOk()
        ->assertJsonPath('data.can_act', true)
        ->assertJsonPath('data.contract.reference', $contract->reference);
});

it('lets the talent advance their step', function () {
    $talent = Talent::factory()->create();
    $contract = roomContract($talent); // current = quote (talent)

    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.contracts.advance', $contract), ['fields' => ['amount' => 3000]])
        ->assertOk();

    expect((float) $contract->fresh()->agreed_amount)->toBe(3000.0);
    expect($contract->fresh()->currentStep->key)->toBe('agreement');
});

it('returns 422 when it is not the talent’s turn', function () {
    $talent = Talent::factory()->create();
    $contract = app(ContractService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => $talent->id,
        'title' => 'X',
        'initiated_by' => 'brand',
    ], ContractFlow::factory()->standard()->create()); // current = brief (brand)

    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.contracts.advance', $contract), ['fields' => ['amount' => 1]])
        ->assertStatus(422);
});

it('posts a free message to the thread', function () {
    $talent = Talent::factory()->create();
    $contract = roomContract($talent);

    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.contracts.message', $contract), ['body' => 'Hi there'])
        ->assertOk();

    expect($contract->messages()->where('type', 'message')->where('body', 'Hi there')->count())->toBe(1);
});

it('forbids acting on another talent’s contract', function () {
    $owner = Talent::factory()->create();
    $contract = roomContract($owner);
    $intruder = Talent::factory()->create();

    $this->actingAs($intruder, 'talent')->getJson(route('talent.contracts.thread', $contract))->assertForbidden();
    $this->actingAs($intruder, 'talent')->postJson(route('talent.contracts.advance', $contract), ['fields' => ['amount' => 1]])->assertForbidden();
});

it('filters the inbox by status', function () {
    $talent = Talent::factory()->create();
    roomContract($talent); // awaiting_talent
    Contract::factory()->create(['talent_id' => $talent->id, 'status' => 'completed']);

    $this->actingAs($talent, 'talent')->getJson(route('talent.contracts.data', ['status' => 'awaiting_talent']))
        ->assertOk()->assertJsonPath('meta.pagination.total', 1);
    $this->actingAs($talent, 'talent')->getJson(route('talent.contracts.data', ['status' => 'completed']))
        ->assertOk()->assertJsonPath('meta.pagination.total', 1);
});
