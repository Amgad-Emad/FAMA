<?php

namespace App\States\Review;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Review lifecycle (talent-spec): submitted (pending) → approved | rejected;
 * an approved review can later be hidden (→ rejected). `is_approved` is synced
 * by App\Listeners\SyncStateProjections.
 */
abstract class ReviewState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class, Approved::class)
            ->allowTransition(Pending::class, Rejected::class)
            ->allowTransition(Approved::class, Rejected::class);
    }
}
