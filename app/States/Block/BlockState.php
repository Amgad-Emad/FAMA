<?php

namespace App\States\Block;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Block lifecycle (talent-spec): visible ⇄ hidden. `is_visible` is synced by
 * App\Listeners\SyncStateProjections. Seeding/adding/editing/reordering/removing
 * are handled by App\Services\ProfileBlockService (not persisted states).
 */
abstract class BlockState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Visible::class)
            ->allowTransition(Visible::class, Hidden::class)
            ->allowTransition(Hidden::class, Visible::class);
    }
}
