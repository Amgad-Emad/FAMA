<?php

namespace App\Actions\Deals;

use App\Actions\Contracts\Action;
use App\Deals\DealProgression;
use App\Deals\StepHandlerFactory;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Complete the deal's current step on behalf of an actor: the step_type handler
 * validates the input and applies side effects, the step is completed (payload
 * saved, system_event posted), and the deal advances to the next actor. Message
 * steps also post the actor's message to the thread.
 */
class AdvanceDeal implements Action
{
    public function __construct(
        private readonly StepHandlerFactory $handlers,
        private readonly DealProgression $progression,
    ) {}

    /**
     * @param  array<string, mixed>  $input  the actor's submission for the step
     * @param  string  $role  brand | talent | admin (the acting side)
     * @param  Model|null  $actor  the acting model (recorded as completed_by)
     */
    public function __invoke(Deal $deal, array $input, string $role, ?Model $actor = null): Deal
    {
        $step = $deal->currentStep;

        if ($step === null || ! $step->status->isCurrent()) {
            throw new InvalidArgumentException('This deal has no step awaiting action.');
        }

        if (! ($step->actor === $role || $step->actor === 'both')) {
            throw new InvalidArgumentException('It is not your turn on this deal.');
        }

        $handler = $this->handlers->forStep($step);
        $payload = $handler->validate($step, $input);

        $handler->apply($deal, $step, $payload);
        $this->progression->finishStep($deal, $step, $payload, $actor, $handler->summary($step, $payload));

        // A message step's body is echoed into the thread as a real chat line.
        if ($step->step_type === 'message' && $actor !== null && ! empty($payload['body'])) {
            $deal->messages()->create([
                'deal_step_id' => $step->id,
                'sender_type' => $actor->getMorphClass(),
                'sender_id' => $actor->getKey(),
                'sender_role' => $role,
                'type' => 'message',
                'body' => $payload['body'],
                'status' => 'sent',
            ]);
        }

        $this->progression->activateNext($deal);

        return $deal->refresh();
    }
}
