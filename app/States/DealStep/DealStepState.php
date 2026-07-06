<?php

namespace App\States\DealStep;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Deal-step lifecycle (talent-spec). pending → active → awaiting_action →
 * completed, with side exits skipped | rejected. Only one step per deal is
 * active/awaiting_action at a time (enforced by the engine).
 *
 * Reject-loop: when a later step is rejected the engine sends the disputed work
 * back — the rejected step goes completed → rejected → awaiting_action (reopened
 * for a redo), and the steps after it reset awaiting_action → pending so they
 * re-run.
 */
abstract class DealStepState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class, Active::class)
            ->allowTransition(Pending::class, Skipped::class)
            ->allowTransition(Active::class, AwaitingAction::class)
            ->allowTransition(Active::class, Completed::class)
            ->allowTransition(Active::class, Skipped::class)
            ->allowTransition(AwaitingAction::class, Completed::class)
            ->allowTransition(AwaitingAction::class, Pending::class)
            ->allowTransition(AwaitingAction::class, Skipped::class)
            ->allowTransition(Completed::class, Rejected::class)
            ->allowTransition(Completed::class, Pending::class)   // tail reset on reject-loop
            ->allowTransition(Rejected::class, AwaitingAction::class);
    }

    /**
     * True while this step is the deal's current step (live, not yet resolved).
     */
    public function isCurrent(): bool
    {
        return in_array(static::$name, ['active', 'awaiting_action'], true);
    }

    /**
     * True once the step is resolved (no longer the current step).
     */
    public function isResolved(): bool
    {
        return in_array(static::$name, ['completed', 'skipped'], true);
    }
}
