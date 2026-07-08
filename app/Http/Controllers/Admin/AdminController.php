<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

/**
 * Base controller for the admin dashboard (the `admin` guard). Page access is
 * gated by `can:` middleware on the route groups; every action delegates to a
 * Phase 3A admin service, which re-authorizes the acting admin (defense in depth)
 * and audits the change.
 */
abstract class AdminController extends Controller
{
    protected function admin(): User
    {
        return auth('admin')->user();
    }
}
