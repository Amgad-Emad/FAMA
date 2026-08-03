<?php

namespace App\States\Comment;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Comment lifecycle (feed-spec §5): born published (no draft stage);
 * published ⇄ hidden (moderation); published|hidden → removed (terminal).
 * A removed/deleted comment WITH replies renders as a tombstone (D12).
 */
abstract class CommentState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Published::class)
            ->allowTransition(Published::class, Hidden::class)
            ->allowTransition(Hidden::class, Published::class)
            ->allowTransition(Published::class, Removed::class)
            ->allowTransition(Hidden::class, Removed::class);
    }
}
