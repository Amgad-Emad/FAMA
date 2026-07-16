<?php

namespace App\Actions\Contracting;

use App\Actions\Contracts\Action;
use App\Contracting\ContractProgression;
use App\Contracting\StepHandlerFactory;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Complete the contract's current step on behalf of an actor: the step_type handler
 * validates the input and applies side effects, the step is completed (payload
 * saved, system_event posted), and the contract advances to the next actor. Message
 * steps also post the actor's message to the thread.
 */
class AdvanceContract implements Action
{
    public function __construct(
        private readonly StepHandlerFactory $handlers,
        private readonly ContractProgression $progression,
    ) {}

    /**
     * @param  array<string, mixed>  $input  the actor's submission for the step
     * @param  string  $role  brand | talent | admin (the acting side)
     * @param  Model|null  $actor  the acting model (recorded as completed_by)
     */
    public function __invoke(Contract $contract, array $input, string $role, ?Model $actor = null): Contract
    {
        $step = $contract->currentStep;

        if ($step === null || ! $step->status->isCurrent()) {
            throw new InvalidArgumentException('This contract has no step awaiting action.');
        }

        if (! ($step->actor === $role || $step->actor === 'both')) {
            throw new InvalidArgumentException('It is not your turn on this contract.');
        }

        $handler = $this->handlers->forStep($step);
        $payload = $handler->validate($step, $input);

        $handler->apply($contract, $step, $payload);
        $this->progression->finishStep($contract, $step, $payload, $actor, $handler->summary($step, $payload));

        // A message step's body is echoed into the thread as a real chat line.
        if ($step->step_type === 'message' && $actor !== null && ! empty($payload['body'])) {
            $contract->messages()->create([
                'contract_step_id' => $step->id,
                'sender_type' => $actor->getMorphClass(),
                'sender_id' => $actor->getKey(),
                'sender_role' => $role,
                'type' => 'message',
                'body' => $payload['body'],
                'status' => 'sent',
            ]);
        }

        $this->progression->activateNext($contract);

        return $contract->refresh();
    }
}
