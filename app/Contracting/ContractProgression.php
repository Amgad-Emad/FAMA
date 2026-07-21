<?php

namespace App\Contracting;

use App\Events\ContractCompleted as ContractCompletedEvent;
use App\Models\Contract;
use App\Models\ContractMessage;
use App\Models\ContractStep;
use App\States\Contract\AwaitingAdmin;
use App\States\Contract\AwaitingBrand;
use App\States\Contract\AwaitingTalent;
use App\States\Contract\Completed as ContractCompleted;
use App\States\Contract\ContractState;
use App\States\ContractStep\Active;
use App\States\ContractStep\AwaitingAction;
use App\States\ContractStep\Completed as StepCompleted;
use Illuminate\Database\Eloquent\Model;

/**
 * The step-progression engine shared by the contract actions. Owns the invariants:
 * exactly one step is active/awaiting_action at a time, contract.status mirrors the
 * current step's actor, system/automatic steps complete themselves, and every
 * completion posts a system_event. Always called inside ContractService's
 * transaction.
 */
class ContractProgression
{
    public function __construct(private readonly StepHandlerFactory $handlers) {}

    /**
     * Activate the next pending step. Automatic steps (system actor / auto
     * payment) complete themselves and recurse; the first human step becomes
     * awaiting_action and flips the contract status to its actor. No pending steps
     * left → the contract completes.
     */
    public function activateNext(Contract $contract): void
    {
        $next = $contract->steps()->where('status', 'pending')->orderBy('position')->first();

        if ($next === null) {
            $this->complete($contract);

            return;
        }

        $next->status->transitionTo(Active::class);

        if ($this->handlers->forStep($next)->isAutomatic($next)) {
            $handler = $this->handlers->forStep($next);
            $this->finishStep($contract, $next, [], null, $handler->summary($next, []));
            $this->activateNext($contract);

            return;
        }

        $next->status->transitionTo(AwaitingAction::class);
        $contract->current_step_id = $next->id;
        $contract->save();
        $this->moveContractTo($contract, $this->stateForActor($next->actor));
    }

    /**
     * Transition the contract status, tolerating a no-op when consecutive steps have
     * the same actor (spatie disallows a same-state self-transition).
     *
     * @param  class-string<ContractState>  $target
     */
    public function moveContractTo(Contract $contract, string $target): void
    {
        if (! $contract->status->equals($target)) {
            $contract->status->transitionTo($target);
        }
    }

    /**
     * Mark a step completed: persist its payload + who completed it, transition
     * to completed, and post the handler's summary as a system_event.
     *
     * @param  array<string, mixed>  $payload
     */
    public function finishStep(Contract $contract, ContractStep $step, array $payload, ?Model $actor, string $summary): void
    {
        $step->payload = $payload;
        $step->completed_at = now();

        if ($actor !== null) {
            $step->completedBy()->associate($actor);
        }

        $step->save();
        $step->status->transitionTo(StepCompleted::class);

        $this->postSystemEvent($contract, $step, $summary, $this->stepEventMeta($step, $payload));
    }

    /**
     * Structured translation descriptor for a step-completion event: a verb key
     * (derived from the step type) + the params needed to render it in any
     * locale. Rejections/skips carry their own meta from their call sites.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function stepEventMeta(ContractStep $step, array $payload = []): array
    {
        $params = ['actor' => $step->actor, 'step_key' => $step->key, 'step_name' => $step->name];

        if ($step->step_type === 'payment') {
            $params['pct'] = (int) data_get($step->settings, 'percentage', 0);
        }

        return [
            'key' => match ($step->step_type) {
                'form' => 'submitted',
                'upload' => 'delivered',
                'approval' => 'approved',
                'payment' => 'paid',
                'contract' => 'signed',
                'schedule' => 'scheduled',
                'message' => 'sent',
                default => 'completed',
            },
            'params' => $params,
        ];
    }

    /**
     * Append an immutable system event to the contract thread. `$meta` (a
     * {key, params} descriptor) lets the renderer localize the body; `body`
     * remains the English fallback.
     *
     * @param  array<string, mixed>|null  $meta
     */
    public function postSystemEvent(Contract $contract, ?ContractStep $step, string $body, ?array $meta = null): ContractMessage
    {
        return $contract->messages()->create([
            'contract_step_id' => $step?->id,
            'sender_type' => null,
            'sender_id' => null,
            'sender_role' => 'system',
            'type' => 'system_event',
            'body' => $body,
            'meta' => $meta,
            'status' => 'sent',
        ]);
    }

    /**
     * The contract status that corresponds to a step actor. `both` and any unmapped
     * actor default to the brand's turn (either party may still act on `both`).
     *
     * @return class-string<ContractState>
     */
    public function stateForActor(string $actor): string
    {
        return match ($actor) {
            'talent' => AwaitingTalent::class,
            'admin' => AwaitingAdmin::class,
            default => AwaitingBrand::class,
        };
    }

    private function complete(Contract $contract): void
    {
        $contract->current_step_id = null;
        $contract->save();
        $contract->status->transitionTo(ContractCompleted::class);
        $this->postSystemEvent($contract, null, 'Contract completed.', ['key' => 'contract_completed', 'params' => []]);

        // Off-critical-path side effects (credibility accrual, review window).
        ContractCompletedEvent::dispatch($contract->refresh());
    }
}
