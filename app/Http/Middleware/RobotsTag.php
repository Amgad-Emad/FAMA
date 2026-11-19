<?php

namespace App\Http\Middleware;

use App\Support\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends `X-Robots-Tag: noindex, nofollow` on every web response a search
 * engine must not index (ADR-AO) — the same rule as the page's robots meta
 * (`Seo::requestIndexable()`: site indexable + route allowlisted + no
 * ?preview / ?visitor). It covers what a <meta> tag cannot: the Tailwind
 * layouts, JSON endpoints, PDFs and redirects.
 */
class RobotsTag
{
    /**
     * Pass the request on, then mark the response when it must stay out of search.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->headers->has('X-Robots-Tag') && ! Seo::requestIndexable()) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
