<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the request locale for the mobile API from the `Accept-Language`
 * header (the API has no locale-prefixed URLs like the web app). The negotiated
 * locale is applied to both Laravel and mcamara/laravel-localization so that
 * spatie/laravel-translatable attributes (Talent headline/bio, Brand
 * description, TalentType name, …) resolve in the requested language.
 *
 * Only Fama's supported locales (en/ar) are honoured; anything else falls back
 * to the app default. The negotiated locale is echoed back on the
 * `Content-Language` response header so clients can confirm what they received.
 */
class SetApiLocale
{
    /**
     * Negotiate and apply the locale, then tag the response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(LaravelLocalization::getSupportedLocales());
        $locale = $this->negotiate($request->header('Accept-Language'), $supported);

        LaravelLocalization::setLocale($locale);
        app()->setLocale($locale);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    /**
     * Pick the best supported locale from a raw `Accept-Language` value
     * (e.g. "ar-EG,ar;q=0.9,en;q=0.8"), honouring quality weights and matching
     * on the primary subtag. Returns the app default when nothing matches.
     *
     * @param  list<string>  $supported
     */
    private function negotiate(?string $header, array $supported): string
    {
        $default = config('app.locale', 'en');

        if (blank($header)) {
            return $default;
        }

        $ranked = [];
        foreach (explode(',', $header) as $part) {
            $segments = explode(';q=', trim($part));
            $tag = strtolower(trim($segments[0]));
            $quality = isset($segments[1]) ? (float) $segments[1] : 1.0;

            if ($tag === '') {
                continue;
            }

            // Match on the primary subtag: "ar-EG" and "ar" both target "ar".
            $primary = explode('-', $tag)[0];
            $ranked[] = ['locale' => $primary, 'q' => $quality];
        }

        usort($ranked, fn (array $a, array $b): int => $b['q'] <=> $a['q']);

        foreach ($ranked as $candidate) {
            if (in_array($candidate['locale'], $supported, true)) {
                return $candidate['locale'];
            }
        }

        return $default;
    }
}
