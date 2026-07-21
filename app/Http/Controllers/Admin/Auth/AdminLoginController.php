<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The dedicated staff login (GET|POST /admin/login) — fully separate from the
 * public role-aware login: its own route, view, and request pinned to the
 * `admin` guard. Logout stays on the shared route('logout') (Guards::logout
 * clears every guard).
 */
class AdminLoginController extends Controller
{
    /**
     * The staff console sign-in screen.
     */
    public function create(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Authenticate against the admin guard and land on the admin dashboard.
     */
    public function store(AdminLoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Same multi-guard rationale as the public login: never honour a stale
        // url.intended that may belong to another guard's area.
        $request->session()->forget('url.intended');

        return redirect()->route('admin.dashboard');
    }
}
