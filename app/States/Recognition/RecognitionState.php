<?php

namespace App\States\Recognition;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Recognition lifecycle (ADR-Z) — deliberately identical to ReviewState:
 * submitted (pending) → approved | rejected, and an approved recognition can
 * later be hidden (→ rejected). Both are peer testimony moderated by the same
 * admin queue, so they earn the same lifecycle rather than a parallel one.
 * `is_approved` is synced by App\Listeners\SyncStateProjections.
 */
abstract class RecognitionState extends State
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
