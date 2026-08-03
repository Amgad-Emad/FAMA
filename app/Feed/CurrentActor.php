<?php

namespace App\Feed;

use Illuminate\Support\Facades\Auth;

/**
 * Resolves the acting feed identity across the participating session guards
 * (feed-spec §2). Checked in priority order talent → brand (a session can be
 * authenticated on both; the participant-most identity wins). Admins resolve
 * to null — they are not actors. Guests resolve to null and that is a normal,
 * non-error outcome.
 *
 * Deliberately NOT memoized: the guards already cache their users per
 * request, and instance state would leak across requests in feature tests
 * and long-lived runtimes (Octane). Controllers inject this service and
 * views receive `$currentActor` — nothing in the feed calls Auth::guard()
 * directly.
 */
class CurrentActor
{
    /**
     * The guards that can hold an Actor, in resolution priority order. The
     * session guards serve the web; `sanctum` serves the mobile API (its
     * tokenable is a Talent or Brand — admins fail the Actor check).
     *
     * @var list<string>
     */
    private const GUARDS = ['talent', 'brand', 'sanctum'];

    /**
     * The acting Actor, or null for guests and admin-only sessions.
     */
    public function resolve(): ?Actor
    {
        foreach (self::GUARDS as $guard) {
            $user = Auth::guard($guard)->user();

            if ($user instanceof Actor) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Whether an Actor is present (a participating identity, not a guest).
     */
    public function check(): bool
    {
        return $this->resolve() !== null;
    }
}
