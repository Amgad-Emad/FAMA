<?php

namespace App\Policies;

use App\Feed\Actor;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Comment authorization (feed-spec §2): guests read; any Actor writes;
 * edits are the author inside the 15-minute window (D11); deletes are the
 * author any time or a moderating admin.
 */
class CommentPolicy extends BasePolicy
{
    /**
     * Anyone — including guests — may read comment threads.
     */
    public function view(?Authenticatable $user, ?Comment $comment = null): bool
    {
        return true;
    }

    /**
     * Only talents may comment. Brands participate by liking and by following
     * talents — they do not comment (product rule).
     */
    public function create(Authenticatable $user): bool
    {
        return $user instanceof \App\Models\Talent;
    }

    /**
     * The author, inside the edit window — or a moderating admin.
     */
    public function update(Authenticatable $user, Comment $comment): bool
    {
        if ($user instanceof User) {
            return $user->can('moderate-content');
        }

        return $user instanceof Actor && $this->isAuthor($user, $comment) && $comment->isEditable();
    }

    /**
     * The author (any time) or a moderating admin.
     */
    public function delete(Authenticatable $user, Comment $comment): bool
    {
        if ($user instanceof User) {
            return $user->can('moderate-content');
        }

        return $user instanceof Actor && $this->isAuthor($user, $comment);
    }

    /**
     * Whether the actor is the comment's morph author.
     */
    private function isAuthor(Actor $actor, Comment $comment): bool
    {
        return $comment->author_type === $actor->getMorphClass()
            && (string) $comment->author_id === (string) $actor->getKey();
    }
}
