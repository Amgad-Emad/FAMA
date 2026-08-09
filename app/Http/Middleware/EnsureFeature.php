<?php

namespace App\Http\Middleware;

use App\Support\Features;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate a route (group) behind a release feature flag. A disabled feature 404s —
 * the route stays registered (so the check is runtime + testable) but is
 * unreachable. Usage: ->middleware('feature:brands').
 */
class EnsureFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless(Features::enabled($feature), 404);

        return $next($request);
    }
}
