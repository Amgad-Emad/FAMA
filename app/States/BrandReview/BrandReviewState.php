<?php

namespace App\States\BrandReview;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Brand-review lifecycle (brand-spec) — mirrors the talent-side review flow.
 * submitted (pending) → approved | rejected; an approved review can later be
 * hidden (→ rejected). `is_approved` is a synced projection
 * (App\Listeners\SyncStateProjections). The brand can never edit it.
 */
abstract class BrandReviewState extends State
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
