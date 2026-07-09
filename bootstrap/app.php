<?php

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;
use Spatie\QueryBuilder\Exceptions\InvalidQuery;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // mcamara/laravel-localization route middleware aliases (the package
        // does not register them itself). Applied to the locale route group in
        // routes/web.php. The Sanctum ability middleware (used by routes/api.php
        // to scope tokens) is likewise not auto-aliased in Laravel 11+.
        $middleware->alias([
            'localize' => LaravelLocalizationRoutes::class,
            'localizationRedirect' => LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => LocaleSessionRedirect::class,
            'localeCookieRedirect' => LocaleCookieRedirect::class,
            'localeViewPath' => LaravelLocalizationViewPath::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render errors as JSON for the API and for any Ajax request that asks
        // for JSON (the http.js wrapper sets Accept: application/json).
        $wantsJson = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($wantsJson);

        // Validation errors -> the standard envelope with the field error bag,
        // so http.js can surface them without a page reload.
        $exceptions->render(function (ValidationException $e, Request $request) use ($wantsJson) {
            if ($wantsJson($request)) {
                return ApiResponse::error(
                    message: $e->getMessage(),
                    errors: $e->errors(),
                    status: $e->status,
                );
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($wantsJson) {
            if ($wantsJson($request)) {
                return ApiResponse::error(message: $e->getMessage(), status: 401);
            }
        });

        // Domain-rule violations from the service layer (e.g. ineligible block,
        // duplicate profession) surface as 422 envelopes.
        $exceptions->render(function (InvalidArgumentException $e, Request $request) use ($wantsJson) {
            if ($wantsJson($request)) {
                return ApiResponse::error(message: $e->getMessage(), status: 422);
            }
        });

        // Illegal state-machine transition (e.g. publishing from an invalid state).
        $exceptions->render(function (CouldNotPerformTransition $e, Request $request) use ($wantsJson) {
            if ($wantsJson($request)) {
                return ApiResponse::error(message: $e->getMessage(), status: 422);
            }
        });

        // Missing record (explicit ->firstOrFail() or a route-model-binding miss).
        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($wantsJson) {
            if ($wantsJson($request)) {
                return ApiResponse::error(message: __('Resource not found.'), status: 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($wantsJson) {
            if ($wantsJson($request)) {
                return ApiResponse::error(message: __('Resource not found.'), status: 404);
            }
        });

        // Policy / ability / abort(403) denials.
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) use ($wantsJson) {
            if ($wantsJson($request)) {
                return ApiResponse::error(message: $e->getMessage() ?: __('This action is unauthorized.'), status: 403);
            }
        });

        // A bad discovery/search query (unknown filter or sort) → 400 envelope,
        // instead of the framework's bare HttpException JSON (spatie/query-builder).
        $exceptions->render(function (InvalidQuery $e, Request $request) use ($wantsJson) {
            if ($wantsJson($request)) {
                return ApiResponse::error(message: $e->getMessage(), status: $e->getStatusCode());
            }
        });

        // Rate-limit trip — surface the retry window in meta so clients can back off.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($wantsJson) {
            if ($wantsJson($request)) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? null;

                return ApiResponse::error(
                    message: __('Too many requests. Please slow down.'),
                    status: 429,
                    meta: $retryAfter !== null ? ['retry_after' => (int) $retryAfter] : [],
                );
            }
        });

        // Catch-all for unexpected (5xx-class) failures on the API: fail-log to the
        // `api` channel with request context, then return a clean 500 envelope
        // (details hidden unless debugging). Expected HTTP exceptions are already
        // shaped above, so they are skipped here.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') || $e instanceof HttpExceptionInterface) {
                return null;
            }

            $user = $request->user();

            Log::channel('api')->error('API request failed', [
                'method' => $request->method(),
                'path' => $request->path(),
                'entity' => $user !== null ? class_basename($user).':'.$user->getAuthIdentifier() : null,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return ApiResponse::error(
                message: config('app.debug') ? $e->getMessage() : __('Something went wrong.'),
                status: 500,
            );
        });
    })->create();
