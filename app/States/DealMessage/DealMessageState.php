<?php

namespace App\States\DealMessage;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Deal-message lifecycle (talent-spec). sent → read; `read_at` is the synced
 * projection of the Read state. system_event / action_summary messages are
 * immutable audit lines — the engine never marks them read (guarded in
 * DealService::markThreadRead), so they stay 'sent'.
 */
abstract class DealMessageState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Sent::class)
            ->allowTransition(Sent::class, Read::class);
    }
}
