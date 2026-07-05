<?php

namespace App\States\ServiceStatus;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Service lifecycle (talent-spec): created → active ⇄ paused → removed.
 * `is_active` is synced by App\Listeners\SyncStateProjections; removal is a delete.
 */
abstract class ServiceStatusState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Active::class)
            ->allowTransition(Active::class, Paused::class)
            ->allowTransition(Paused::class, Active::class);
    }
}
