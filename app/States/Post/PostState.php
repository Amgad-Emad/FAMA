<?php

namespace App\States\Post;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Post lifecycle (feed-spec §5): draft → published; published ⇄ hidden
 * (moderation / auto-hide / restore); published|hidden → removed (terminal).
 * Author deletes are SOFT DELETES, orthogonal to this machine (D12) — the
 * machine models visibility, not ownership.
 */
abstract class PostState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Published::class)
            ->allowTransition(Published::class, Hidden::class)
            ->allowTransition(Hidden::class, Published::class)
            ->allowTransition(Published::class, Removed::class)
            ->allowTransition(Hidden::class, Removed::class);
    }
}
