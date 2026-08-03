<?php

namespace App\Models\Concerns;

use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared feed identity for the two participating entities (Talent and Brand).
 *
 * Gives an actor its feed relations — authored posts, comments, likes given,
 * and the two sides of the follow graph — plus the identity readers the feed
 * renders everywhere (Username, display name, avatar). The Actor contract
 * (Prompt 5C) formalizes these into the cross-guard "who is acting" concept.
 */
trait HasFeedIdentity
{
    /**
     * Posts authored by this actor (talents today; brands by policy flip —
     * ADR-F1).
     *
     * @return MorphMany<Post, $this>
     */
    public function posts(): MorphMany
    {
        return $this->morphMany(Post::class, 'author');
    }

    /**
     * Comments written by this actor.
     *
     * @return MorphMany<Comment, $this>
     */
    public function feedComments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'author');
    }

    /**
     * Follow edges pointing AT this actor (its audience).
     *
     * @return MorphMany<Follow, $this>
     */
    public function followers(): MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    /**
     * Follow edges this actor created (who it follows).
     *
     * @return MorphMany<Follow, $this>
     */
    public function following(): MorphMany
    {
        return $this->morphMany(Follow::class, 'follower');
    }

    /**
     * Likes this actor has given.
     *
     * @return MorphMany<Like, $this>
     */
    public function likesGiven(): MorphMany
    {
        return $this->morphMany(Like::class, 'liker');
    }

    /**
     * The public Username (the slug column on both entities).
     */
    public function feedUsername(): string
    {
        return (string) $this->slug;
    }

    /**
     * The display name the feed renders (Talent: display_name; Brand: name).
     */
    public function feedDisplayName(): string
    {
        return (string) ($this->display_name ?? $this->name);
    }

    /**
     * The avatar the feed renders (Talent: avatar collection; Brand: logo).
     * Callers must eager-load `media` — the accessor resolves from it.
     */
    public function feedAvatarUrl(): ?string
    {
        return $this->avatar_url ?? $this->logo_url ?? null;
    }
}
