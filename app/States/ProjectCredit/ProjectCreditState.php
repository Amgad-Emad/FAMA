<?php

namespace App\States\ProjectCredit;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Credit lifecycle (ADR-AA).
 *
 * `pending → accepted | declined` is the CONFIRMATION the spec requires before a
 * credit reaches the tagged person's portfolio. An accepted credit can later be
 * withdrawn (→ declined) — someone who changes their mind about being publicly
 * associated with a project must be able to say so, and that is the same
 * transition as refusing it in the first place.
 *
 * `listed` is the terminal state of an OFF-PLATFORM credit: there is nobody to
 * confirm it and it never leaves this project, so it never enters the pending
 * flow at all. Nothing transitions into or out of it.
 */
abstract class ProjectCreditState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class, Accepted::class)
            ->allowTransition(Pending::class, Declined::class)
            ->allowTransition(Accepted::class, Declined::class);
    }

    /**
     * Whether a credit in this state may be shown publicly — on the project page
     * and on the collaborator's portfolio.
     */
    public function isPublic(): bool
    {
        return in_array(static::$name, ['accepted', 'listed'], true);
    }
}
