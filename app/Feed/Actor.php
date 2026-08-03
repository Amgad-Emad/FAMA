<?php

namespace App\Feed;

/**
 * The feed's single concept of "who is acting" (feed-spec §2), implemented by
 * Talent and Brand (via the HasFeedIdentity concern). Admins are deliberately
 * NOT actors — they moderate, they don't participate.
 *
 * Kept under App\Feed (mirroring App\Contracting) — App\Contracts would
 * collide with the domain word "Contracts".
 */
interface Actor
{
    /**
     * The enforced morph alias ('talent' | 'brand') — how the actor is stored
     * in every feed morph column.
     */
    public function getMorphClass();

    /**
     * The actor's primary key.
     */
    public function getKey();

    /**
     * The public Username (the slug column).
     */
    public function feedUsername(): string;

    /**
     * The display name the feed renders.
     */
    public function feedDisplayName(): string;

    /**
     * The avatar/logo URL (media-library accessor; callers eager-load media).
     */
    public function feedAvatarUrl(): ?string;
}
