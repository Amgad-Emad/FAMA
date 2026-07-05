<?php

namespace App\Support\Auth;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Small helper that treats Fama's three session guards (admin/brand/talent) as
 * a set. Because a browser session can be authenticated on more than one guard,
 * "current" means the first guard that reports an authenticated user, checked in
 * role priority order. Views and shared UI use these instead of Auth::user(),
 * which would only ever inspect the default guard.
 */
final class Guards
{
    /**
     * All Fama session guard names, in resolution priority order.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return UserRole::values();
    }

    /**
     * The name of the first authenticated guard, or null if none.
     */
    public static function current(): ?string
    {
        foreach (self::names() as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

    /**
     * The currently authenticated user from whichever Fama guard is active.
     */
    public static function user(): ?Authenticatable
    {
        $guard = self::current();

        return $guard !== null ? Auth::guard($guard)->user() : null;
    }

    /**
     * Log the session out of every Fama guard (used by the shared logout).
     */
    public static function logout(Request $request): void
    {
        foreach (self::names() as $guard) {
            Auth::guard($guard)->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
