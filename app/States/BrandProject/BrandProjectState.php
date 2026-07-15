<?php

namespace App\States\BrandProject;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Project lifecycle (brand-spec §5). draft → open → in_progress → completed;
 * cancellable from any active state. `is_public` toggles listed ⇄ private
 * independently of status; soft-delete removes it. A completed project becomes
 * a showcase on the brand profile.
 */
abstract class BrandProjectState extends State
{
    public static function config(): StateConfig
    {
        $config = parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Open::class)
            ->allowTransition(Open::class, InProgress::class)
            ->allowTransition(InProgress::class, Completed::class);

        foreach ([Draft::class, Open::class, InProgress::class] as $from) {
            $config->allowTransition($from, Cancelled::class);
        }

        return $config;
    }

    /**
     * True while the project is live (not completed/cancelled).
     */
    public function isActive(): bool
    {
        return in_array(static::$name, ['draft', 'open', 'in_progress'], true);
    }
}
