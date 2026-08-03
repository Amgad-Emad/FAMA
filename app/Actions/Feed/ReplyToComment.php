<?php

namespace App\Actions\Feed;

use App\Feed\Actor;
use App\Models\Comment;

/**
 * Reply to a comment (feed-spec §3, ADR-F7). Thin by design: CreateComment
 * owns the single write path — including the depth-two re-attach (replying to
 * a reply attaches to its parent with an auto-prefixed @mention) and the
 * counter/mention/link pipeline.
 */
class ReplyToComment
{
    public function __construct(private readonly CreateComment $createComment)
    {
    }

    /**
     * Persist the reply and return it.
     */
    public function __invoke(Actor $author, Comment $parent, string $body): Comment
    {
        return ($this->createComment)($author, $parent->post, $body, $parent);
    }
}
