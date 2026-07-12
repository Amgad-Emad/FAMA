<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Auth\Guards;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles the single, role-aware web login/logout for all three guards. Which
 * guard is used is decided by the LoginRequest (from the submitted `role`).
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
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
