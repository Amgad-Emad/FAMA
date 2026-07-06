<?php

use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // mcamara/laravel-localization route middleware aliases (the package
        // does not register them itself). Applied to the locale route group in
        // routes/web.php.
        $middleware->alias([
            'localize' => LaravelLocalizationRoutes::class,
            'localizationRedirect' => LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => LocaleSessionRedirect::class,
            'localeCookieRedirect' => LocaleCookieRedirect::class,
            'localeViewPath' => LaravelLocalizationViewPath::class,
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
    })->create();
