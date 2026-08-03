<?php

namespace App\Policies;

use App\Feed\Actor;
use App\Models\Post;
use App\Models\Talent;
use App\Models\User;
use App\States\Post\Published;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Post authorization (feed-spec §2). Guests read everything published;
 * CREATE is gated to talents — flipping brands on is this one method (ADR-F1);
 * UPDATE is the author inside the 15-minute window (D11); DELETE is the author
 * any time (their content, soft-deleted) or a moderating admin; PIN is the
 * author only.
 */
class PostPolicy extends BasePolicy
{
    /**
     * Anyone — including guests — may browse the feed.
     */
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    /**
     * Guests see published posts; the author sees their own in any state;
     * moderating admins see everything.
     */
    public function view(?Authenticatable $user, Post $post): bool
    {
        if ($post->state instanceof Published && $post->deleted_at === null) {
            return true;
        }

        if ($user instanceof User) {
            return $user->can('moderate-content');
        }

        return $user instanceof Actor && $this->isAuthor($user, $post);
    }

    /**
     * Talents only (ADR-F1 — brands are enabled here, nowhere else).
     */
    public function create(Authenticatable $user): bool
    {
        return $user instanceof Talent;
    }

    /**
     * The author, inside the edit window (D11) — or a moderating admin.
     */
    public function update(Authenticatable $user, Post $post): bool
    {
        if ($user instanceof User) {
            return $user->can('moderate-content');
        }

        return $user instanceof Actor && $this->isAuthor($user, $post) && $post->isEditable();
    }

    /**
     * The author (any time — deleting your own content is not windowed) or a
     * moderating admin.
     */
    public function delete(Authenticatable $user, Post $post): bool
    {
        if ($user instanceof User) {
            return $user->can('moderate-content');
        }

        return $user instanceof Actor && $this->isAuthor($user, $post);
    }

    /**
     * The author only (max one pinned post per talent — enforced in the
     * action).
     */
    public function pin(Authenticatable $user, Post $post): bool
    {
        return $user instanceof Actor && $this->isAuthor($user, $post);
    }

    /**
     * Whether the actor is the post's morph author.
     */
    private function isAuthor(Actor $actor, Post $post): bool
    {
        return $post->author_type === $actor->getMorphClass()
            && (string) $post->author_id === (string) $actor->getKey();
    }
}
