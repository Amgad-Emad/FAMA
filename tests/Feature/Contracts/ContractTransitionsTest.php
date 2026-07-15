<?php

use App\Models\Contract;
use App\Models\ContractMessage;
use App\Models\ContractStep;
use App\States\Contract\AwaitingBrand;
use App\States\Contract\Completed as ContractCompleted;
use App\States\ContractMessage\Read;
use App\States\ContractStep\Active;
use App\States\ContractStep\AwaitingAction;
use App\States\ContractStep\Completed as StepCompleted;
use App\States\ContractStep\Rejected;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

it('allows a contract to move draft → awaiting_brand → completed', function () {
    $contract = Contract::factory()->create(['status' => 'draft']);

    $contract->status->transitionTo(AwaitingBrand::class);
    $contract->status->transitionTo(ContractCompleted::class);

    expect($contract->status::$name)->toBe('completed');
});

it('forbids an illegal contract transition (completed → awaiting_brand)', function () {
    $contract = Contract::factory()->create(['status' => 'completed']);

    expect(fn () => $contract->status->transitionTo(AwaitingBrand::class))->toThrow(TransitionNotFound::class);
});

it('walks a step pending → active → awaiting_action → completed', function () {
    $step = ContractStep::factory()->create(['status' => 'pending']);

    $step->status->transitionTo(Active::class);
    $step->status->transitionTo(AwaitingAction::class);
    $step->status->transitionTo(StepCompleted::class);

    expect($step->status::$name)->toBe('completed');
});

it('forbids jumping a step straight from pending to completed', function () {
    $step = ContractStep::factory()->create(['status' => 'pending']);

    expect(fn () => $step->status->transitionTo(StepCompleted::class))->toThrow(TransitionNotFound::class);
});

it('reopens a rejected step for a redo (completed → rejected → awaiting_action)', function () {
    $step = ContractStep::factory()->create(['status' => 'completed']);

    $step->status->transitionTo(Rejected::class);
    $step->status->transitionTo(AwaitingAction::class);

    expect($step->status::$name)->toBe('awaiting_action');
});

it('marks a message read once and forbids re-reading (immutability)', function () {
    $message = ContractMessage::factory()->create(['status' => 'sent']);

    $message->status->transitionTo(Read::class);
    expect($message->status::$name)->toBe('read');

    expect(fn () => $message->status->transitionTo(Read::class))->toThrow(TransitionNotFound::class);
});
