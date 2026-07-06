<?php

use App\Models\Deal;
use App\Models\DealMessage;
use App\Models\DealStep;
use App\States\Deal\AwaitingBrand;
use App\States\Deal\Completed as DealCompleted;
use App\States\DealMessage\Read;
use App\States\DealStep\Active;
use App\States\DealStep\AwaitingAction;
use App\States\DealStep\Completed as StepCompleted;
use App\States\DealStep\Rejected;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

it('allows a deal to move draft → awaiting_brand → completed', function () {
    $deal = Deal::factory()->create(['status' => 'draft']);

    $deal->status->transitionTo(AwaitingBrand::class);
    $deal->status->transitionTo(DealCompleted::class);

    expect($deal->status::$name)->toBe('completed');
});

it('forbids an illegal deal transition (completed → awaiting_brand)', function () {
    $deal = Deal::factory()->create(['status' => 'completed']);

    expect(fn () => $deal->status->transitionTo(AwaitingBrand::class))->toThrow(TransitionNotFound::class);
});

it('walks a step pending → active → awaiting_action → completed', function () {
    $step = DealStep::factory()->create(['status' => 'pending']);

    $step->status->transitionTo(Active::class);
    $step->status->transitionTo(AwaitingAction::class);
    $step->status->transitionTo(StepCompleted::class);

    expect($step->status::$name)->toBe('completed');
});

it('forbids jumping a step straight from pending to completed', function () {
    $step = DealStep::factory()->create(['status' => 'pending']);

    expect(fn () => $step->status->transitionTo(StepCompleted::class))->toThrow(TransitionNotFound::class);
});

it('reopens a rejected step for a redo (completed → rejected → awaiting_action)', function () {
    $step = DealStep::factory()->create(['status' => 'completed']);

    $step->status->transitionTo(Rejected::class);
    $step->status->transitionTo(AwaitingAction::class);

    expect($step->status::$name)->toBe('awaiting_action');
});

it('marks a message read once and forbids re-reading (immutability)', function () {
    $message = DealMessage::factory()->create(['status' => 'sent']);

    $message->status->transitionTo(Read::class);
    expect($message->status::$name)->toBe('read');

    expect(fn () => $message->status->transitionTo(Read::class))->toThrow(TransitionNotFound::class);
});
