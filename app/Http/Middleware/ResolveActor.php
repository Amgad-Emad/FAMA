<?php

namespace App\Http\Middleware;

use App\Feed\CurrentActor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the acting feed identity for the request and shares it — request
 * attribute + view data — WITHOUT ever rejecting. Guests are first-class
 * feed readers (ADR-F8); this middleware only answers "who is here", never
 * "may they".
 */
class ResolveActor
{
    public function __construct(private readonly CurrentActor $currentActor)
    {
    }

    /**
     * Attach the resolved actor (or null) to the request and the view layer.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $actor = $this->currentActor->resolve();

        $request->attributes->set('feedActor', $actor);
        view()->share('currentActor', $actor);

        return $next($request);
    }
}
