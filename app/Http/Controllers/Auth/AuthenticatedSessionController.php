<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Feed\PendingIntent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Talent;
use App\Services\ProfileCompletionService;
use App\Support\Auth\Guards;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Handles the single, role-aware web login/logout for all three guards. Which
 * guard is used is decided by the LoginRequest (from the submitted `role`).
 */
class AuthenticatedSessionController extends Controller
{
    /** @param  ProfileCompletionService  $completion  decides a talent's landing page */
    public function __construct(private readonly ProfileCompletionService $completion) {}

    /**
     * Display the login view. A guest bounced here from a feed write (follow,
     * like, comment, save) sees what will be finished once they log in.
     */
    public function create(PendingIntent $pendingIntent): View
    {
        return view('auth.login', ['intentNote' => $pendingIntent->loginNote()]);
    }

    /**
     * Handle an incoming authentication request against the resolved guard.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Land on the authenticated guard's own dashboard. We deliberately do NOT
        // honour session('url.intended'): in a multi-guard app the intended URL
        // often belongs to a different guard (e.g. a /brand/* page captured before
        // a talent login), which would bounce the user straight back to login.
        $request->session()->forget('url.intended');

        // A talent lands on Projects once their profile is done (portfolio first, 13 Nov 2026) (every Go Live
        // and Stand out step), otherwise on Profile structure, where the next
        // step is (ADR-AC, 2026-11-04 — prototype FAMA_HOME). A visitor's
        // pending feed action (follow / like / comment / save) has already been
        // replayed by the Login event, so the old "return to the post" slot is
        // dropped.
        $request->session()->forget('feed.return_to');

        if ($request->role() === UserRole::Talent) {
            $talent = Auth::guard('talent')->user();

            return redirect()->route($talent instanceof Talent
                ? $this->completion->homeRoute($talent)
                : 'talent.profile.edit');
        }

        // route('dashboard') dispatches to the active guard's dashboard.
        return redirect()->route('dashboard');
    }

    /**
     * Destroy the authenticated session across every Fama guard.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Guards::logout($request);

        return redirect('/');
    }
}
