<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TalentProfileController;
use App\Support\Auth\Guards;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| Web routes (locale-prefixed)
|--------------------------------------------------------------------------
|
| Every web route lives inside the mcamara locale group so URLs are prefixed
| with the locale (/ar/...). The default locale (en) is hidden, so /login and
| /ar/login both resolve. Public pages are unguarded; each entity's dashboard
| sits behind its own guard (auth:admin | auth:brand | auth:talent).
|
*/

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], function () {

    // --- Public (no login wall) ---------------------------------------------
    // Public talent (fama.com/{slug}) and brand (fama.com/brands/{slug})
    // profile pages are Phase 1 (they need their tables) and are added here,
    // unguarded, then.
    Route::view('/', 'welcome')->name('home');

    // --- Post-login entry point ---------------------------------------------
    // A single route('dashboard') that dispatches to the active guard's
    // dashboard (keeps Breeze's redirects working across all three entities).
    Route::get('/dashboard', function () {
        $guard = Guards::current();

        return redirect()->route($guard !== null ? "{$guard}.dashboard" : 'login');
    })->name('dashboard');

    // --- Guarded dashboards (one group per guard) ---------------------------
    Route::middleware('auth:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');
    });

    Route::middleware('auth:brand')->prefix('brand')->name('brand.')->group(function () {
        Route::view('/dashboard', 'dashboard')->name('dashboard');
    });

    Route::middleware('auth:talent')->prefix('talent')->name('talent.')
        ->group(base_path('routes/talent.php'));

    // --- Admin profile (Breeze) ---------------------------------------------
    // Runs on the admin guard; brand/talent profile editors are dedicated
    // Phase 1 flows per docs/specs.
    Route::middleware('auth:admin')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Role-aware auth routes (login/register/password/verification/logout).
    require __DIR__.'/auth.php';

    // Public talent profile — fama.com/{slug}. MUST stay last: it is a
    // single-segment catch-all, so all named routes above take precedence. The
    // constraint keeps it to slug-shaped paths.
    Route::get('/{slug}', [TalentProfileController::class, 'show'])
        ->where('slug', '[A-Za-z0-9\-]+')
        ->name('talent.public');
});
