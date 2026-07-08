<?php

namespace App\Deals;

use App\Events\DealCompleted as DealCompletedEvent;
use App\Models\Deal;
use App\Models\DealMessage;
use App\Models\DealStep;
use App\States\Deal\AwaitingAdmin;
use App\States\Deal\AwaitingBrand;
use App\States\Deal\AwaitingTalent;
use App\States\Deal\Completed as DealCompleted;
use App\States\Deal\DealState;
use App\States\DealStep\Active;
use App\States\DealStep\AwaitingAction;
use App\States\DealStep\Completed as StepCompleted;
use Illuminate\Database\Eloquent\Model;

/**
 * The step-progression engine shared by the deal actions. Owns the invariants:
 * exactly one step is active/awaiting_action at a time, deal.status mirrors the
 * current step's actor, system/automatic steps complete themselves, and every
 * completion posts a system_event. Always called inside DealService's
 * transaction.
 */
class DealProgression
{
    public function __construct(private readonly StepHandlerFactory $handlers) {}

    /**
     * Activate the next pending step. Automatic steps (system actor / auto
     * payment) complete themselves and recurse; the first human step becomes
     * awaiting_action and flips the deal status to its actor. No pending steps
     * left → the deal completes.
     */
    public function activateNext(Deal $deal): void
    {
        $next = $deal->steps()->where('status', 'pending')->orderBy('position')->first();

        if ($next === null) {
            $this->complete($deal);

            return;
        }

        $next->status->transitionTo(Active::class);

        if ($this->handlers->forStep($next)->isAutomatic($next)) {
            $handler = $this->handlers->forStep($next);
            $this->finishStep($deal, $next, [], null, $handler->summary($next, []));
            $this->activateNext($deal);

            return;
        }

        $next->status->transitionTo(AwaitingAction::class);
        $deal->current_step_id = $next->id;
        $deal->save();
        $this->moveDealTo($deal, $this->stateForActor($next->actor));
    }

    /**
     * Transition the deal status, tolerating a no-op when consecutive steps have
     * the same actor (spatie disallows a same-state self-transition).
     *
     * @param  class-string<DealState>  $target
     */
    public function moveDealTo(Deal $deal, string $target): void
    {
        if (! $deal->status->equals($target)) {
            $deal->status->transitionTo($target);
        }
    }

    /**
     * Mark a step completed: persist its payload + who completed it, transition
     * to completed, and post the handler's summary as a system_event.
     *
     * @param  array<string, mixed>  $payload
     */
    public function finishStep(Deal $deal, DealStep $step, array $payload, ?Model $actor, string $summary): void
    {
        $step->payload = $payload;
        $step->completed_at = now();

        if ($actor !== null) {
            $step->completedBy()->associate($actor);
        }

        $step->save();
        $step->status->transitionTo(StepCompleted::class);

        $this->postSystemEvent($deal, $step, $summary);
    }

    /**
     * Append an immutable system event to the deal thread.
     */
    public function postSystemEvent(Deal $deal, ?DealStep $step, string $body): DealMessage
    {
        return $deal->messages()->create([
            'deal_step_id' => $step?->id,
            'sender_type' => null,
            'sender_id' => null,
            'sender_role' => 'system',
            'type' => 'system_event',
            'body' => $body,
            'status' => 'sent',
        ]);
    }

    /**
     * The deal status that corresponds to a step actor. `both` and any unmapped
     * actor default to the brand's turn (either party may still act on `both`).
     *
     * @return class-string<DealState>
     */
    public function stateForActor(string $actor): string
    {
        return match ($actor) {
            'talent' => AwaitingTalent::class,
            'admin' => AwaitingAdmin::class,
            default => AwaitingBrand::class,
        };
    }

    private function complete(Deal $deal): void
    {
        $deal->current_step_id = null;
        $deal->save();
        $deal->status->transitionTo(DealCompleted::class);
        $this->postSystemEvent($deal, null, 'Deal completed.');

        // Off-critical-path side effects (credibility accrual, review window).
        DealCompletedEvent::dispatch($deal->refresh());
    }
}
