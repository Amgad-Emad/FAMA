<?php

namespace App\States\Brand;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Brand lifecycle (brand-spec). registered → onboarding → complete → published
 * ⇄ unpublished; suspended from any complete state; soft-delete is the terminal
 * removal (model-level). `is_verified` is an orthogonal one-way flag, not a
 * status. The flags `is_complete`/`is_published`/`is_active` are synced
 * projections (App\Listeners\SyncStateProjections).
 */
abstract class BrandState extends State
{
    public static function config(): StateConfig
    {
        $config = parent::config()
            ->default(Registered::class)
            ->allowTransition(Registered::class, Onboarding::class)
            ->allowTransition(Onboarding::class, Complete::class)
            ->allowTransition(Complete::class, Published::class)
            ->allowTransition(Published::class, Unpublished::class)
            ->allowTransition(Unpublished::class, Published::class);

        // Suspend from any live/complete state; reactivate back to hidden.
        foreach ([Complete::class, Published::class, Unpublished::class] as $from) {
            $config->allowTransition($from, Suspended::class);
        }
        $config->allowTransition(Suspended::class, Unpublished::class);

        return $config;
    }

    /**
     * Past onboarding — the brand can transact (`is_complete`).
     */
    public function isComplete(): bool
    {
        return in_array(static::$name, ['complete', 'published', 'unpublished', 'suspended'], true);
    }
}
