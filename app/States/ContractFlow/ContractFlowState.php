<?php

namespace App\States\ContractFlow;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Contract-flow template lifecycle (Phase 3A admin authoring). draft (being built) →
 * active (usable by the engine; may be marked default) → archived (retired).
 * Reactivation is allowed. `is_active` is a synced projection of this status
 * (App\Listeners\SyncStateProjections); `is_default` is an orthogonal flag.
 */
abstract class ContractFlowState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Active::class)
            ->allowTransition(Active::class, Draft::class)
            ->allowTransition(Active::class, Archived::class)
            ->allowTransition(Draft::class, Archived::class)
            ->allowTransition(Archived::class, Active::class);
    }
}
