<?php

namespace App\States\Availability;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Availability lifecycle (talent-spec): available ⇄ booked ⇄ unavailable.
 * Backed by the existing `talents.availability_status` enum column.
 */
abstract class AvailabilityState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Available::class)
            ->allowTransition(Available::class, Booked::class)
            ->allowTransition(Available::class, Unavailable::class)
            ->allowTransition(Booked::class, Available::class)
            ->allowTransition(Booked::class, Unavailable::class)
            ->allowTransition(Unavailable::class, Available::class)
            ->allowTransition(Unavailable::class, Booked::class);
    }
}
