<?php

namespace App\Actions\Deals;

use App\Actions\Contracts\Action;
use App\Deals\DealProgression;
use App\Models\Deal;
use App\States\DealStep\AwaitingAction;
use App\States\DealStep\Pending;
use App\States\DealStep\Rejected;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Reject the current step and loop the deal back: the disputed earlier work is
 * reopened for a redo, and every step after it resets to pending so the flow
 * re-runs from there. Posts a system_event recording the rejection.
 */
class RejectStep implements Action
{
    public function __construct(private readonly DealProgression $progression) {}

    public function __invoke(Deal $deal, string $role, ?string $reason = null, ?Model $actor = null): Deal
    {
        $current = $deal->currentStep;

        if ($current === null || ! $current->status->isCurrent()) {
            throw new InvalidArgumentException('This deal has no step awaiting action.');
        }

        if (! ($current->actor === $role || $current->actor === 'both')) {
            throw new InvalidArgumentException('It is not your turn on this deal.');
        }

        $target = $deal->steps()
            ->where('position', '<', $current->position)
            ->whereIn('actor', ['brand', 'talent', 'both'])
            ->where('status', 'completed')
            ->reorder('position', 'desc') // clear the relation's default asc order
            ->first();

        if ($target === null) {
            throw new InvalidArgumentException('There is no earlier step to send this back to.');
        }

        $label = $current->actor === 'both' ? ucfirst($role) : ucfirst($current->actor);
        $this->progression->postSystemEvent($deal, $current, $label.' rejected '.$current->name.($reason ? ': '.$reason : '').'.');

        // Reset the tail after the target back to pending so it re-runs.
        foreach ($deal->steps()->where('position', '>', $target->position)->get() as $step) {
            $name = $step->status::$name;

            if ($name === 'active') {
                $step->status->transitionTo(AwaitingAction::class);
                $step->status->transitionTo(Pending::class);
            } elseif (in_array($name, ['awaiting_action', 'completed'], true)) {
                $step->status->transitionTo(Pending::class);
            }

            $step->forceFill(['completed_at' => null, 'completed_by_type' => null, 'completed_by_id' => null])->save();
        }

        // Reopen the target for a redo (passes through the "rejected" state).
        $target->status->transitionTo(Rejected::class);
        $target->status->transitionTo(AwaitingAction::class);
        $target->forceFill(['completed_at' => null, 'completed_by_type' => null, 'completed_by_id' => null])->save();

        $deal->current_step_id = $target->id;
        $deal->save();
        $this->progression->moveDealTo($deal, $this->progression->stateForActor($target->actor));

        return $deal->refresh();
    }
}
