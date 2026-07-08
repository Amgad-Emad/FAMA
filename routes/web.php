<?php

use App\Http\Controllers\BrandProfileController;
use App\Http\Controllers\DiscoveryController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublicReviewController;
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
    // NOTE: no `localeSessionRedirect`. With hideDefaultLocaleInURL=true it traps
    // users on a non-default locale (session `ar` bounces the prefix-less EN URL
    // back to /ar). The URL prefix is the single source of truth for locale.
    'middleware' => ['localizationRedirect', 'localeViewPath'],
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

    Route::middleware('auth:brand')->prefix('brand')->name('brand.')
        ->group(base_path('routes/brand.php'));

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

    // --- Public discovery + talent sub-pages (before the {slug} catch-all) ---
    Route::get('/discover', [DiscoveryController::class, 'index'])->name('discover');
    Route::get('/discover/search', [DiscoveryController::class, 'search'])->name('discover.search');

    // Public brand profile + campaign detail (two-segment paths, so they never
    // collide with the single-segment /{slug} talent catch-all; kept here for
    // clarity). The campaign binding is scoped to its brand.
    Route::get('/brands/{brand:slug}', [BrandProfileController::class, 'show'])->name('brand.public');
    Route::get('/brands/{brand:slug}/campaigns/{campaign:slug}', [BrandProfileController::class, 'campaign'])
        ->scopeBindings()->name('brand.campaign.public');

    Route::get('/{slug}/review', [PublicReviewController::class, 'create'])
        ->where('slug', '[A-Za-z0-9\-]+')->name('talent.review.create');
    Route::post('/{slug}/review', [PublicReviewController::class, 'store'])
        ->where('slug', '[A-Za-z0-9\-]+')->name('talent.review.store');

    Route::get('/{slug}/work/{project}', [ProjectController::class, 'show'])
        ->where('slug', '[A-Za-z0-9\-]+')->name('talent.work');

    // Deal initiation — booking CTA (no-login enquiry capture).
    Route::get('/{slug}/enquire', [EnquiryController::class, 'create'])
        ->where('slug', '[A-Za-z0-9\-]+')->name('talent.enquire');
    Route::post('/{slug}/enquire', [EnquiryController::class, 'store'])
        ->where('slug', '[A-Za-z0-9\-]+')->name('talent.enquire.store');

    // Public talent profile — fama.com/{slug}. MUST stay last: it is a
    // single-segment catch-all, so all named routes above take precedence. The
    // constraint keeps it to slug-shaped paths.
    Route::get('/{slug}', [TalentProfileController::class, 'show'])
        ->where('slug', '[A-Za-z0-9\-]+')
        ->name('talent.public');
});
