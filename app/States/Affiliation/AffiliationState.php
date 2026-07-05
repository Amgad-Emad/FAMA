<?php

namespace App\States\Affiliation;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Agency affiliation lifecycle (talent-spec): current → past → removed.
 * `is_current` is synced by App\Listeners\SyncStateProjections; removal is a delete.
 */
abstract class AffiliationState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Current::class)
            ->allowTransition(Current::class, Past::class);
    }
}
