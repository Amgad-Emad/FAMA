<?php

use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Auth\BrandAuthController;
use App\Http\Controllers\Api\V1\Auth\TalentAuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\DealController;
use App\Http\Controllers\Api\V1\TalentController;
use App\Http\Middleware\SetApiLocale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fama mobile API — v1
|--------------------------------------------------------------------------
|
| Registered under the `api` prefix by bootstrap/app.php, so every path here
| resolves at /api/v1/... . The whole surface is stateless Sanctum token auth
| with the shared JSON envelope; `SetApiLocale` negotiates the response locale
| from Accept-Language, and every route is throttled (a stricter bucket guards
| the credential endpoints). New versions get their own prefix group — the v1
| contract never shifts under existing clients.
|
*/

Route::prefix('v1')
    ->middleware([SetApiLocale::class, 'throttle:api'])
    ->group(function () {
        // ---- Talent authentication -----------------------------------------
        Route::prefix('talent')->group(function () {
            Route::post('register', [TalentAuthController::class, 'register'])->middleware('throttle:auth');
            Route::post('login', [TalentAuthController::class, 'login'])->middleware('throttle:auth');

            Route::middleware(['auth:sanctum', 'abilities:talent'])->group(function () {
                Route::get('me', [TalentAuthController::class, 'me']);
                Route::post('refresh', [TalentAuthController::class, 'refresh']);
                Route::post('logout', [TalentAuthController::class, 'logout']);
            });
        });

        // ---- Brand authentication ------------------------------------------
        Route::prefix('brand')->group(function () {
            Route::post('register', [BrandAuthController::class, 'register'])->middleware('throttle:auth');
            Route::post('login', [BrandAuthController::class, 'login'])->middleware('throttle:auth');

            Route::middleware(['auth:sanctum', 'abilities:brand'])->group(function () {
                Route::get('me', [BrandAuthController::class, 'me']);
                Route::post('refresh', [BrandAuthController::class, 'refresh']);
                Route::post('logout', [BrandAuthController::class, 'logout']);
            });
        });

        // ---- Admin authentication ------------------------------------------
        // No public admin sign-up — staff are provisioned by an existing admin
        // (see AdminAuthController::register, gated by manage-users).
        Route::prefix('admin')->group(function () {
            Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:auth');

            Route::middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
                Route::get('me', [AdminAuthController::class, 'me']);
                Route::post('refresh', [AdminAuthController::class, 'refresh']);
                Route::post('logout', [AdminAuthController::class, 'logout']);
                Route::post('register', [AdminAuthController::class, 'register'])->middleware('abilities:manage-users');
            });
        });

        // ---- Public discovery (read-only) ----------------------------------
        Route::get('talents', [TalentController::class, 'index']);
        Route::get('talents/{talent:slug}', [TalentController::class, 'show']);
        Route::get('brands/{brand:slug}', [BrandController::class, 'show']);

        // ---- Authenticated deal inbox (talent or brand token) --------------
        Route::middleware(['auth:sanctum', 'ability:talent,brand'])->group(function () {
            Route::get('deals', [DealController::class, 'index']);
            Route::get('deals/{deal}', [DealController::class, 'show']);
        });
    });
