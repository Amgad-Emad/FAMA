<?php

use App\Http\Controllers\Brand\AccountController;
use App\Http\Controllers\Brand\ProjectController;
use App\Http\Controllers\Brand\CreativeNeedsController;
use App\Http\Controllers\Brand\DashboardController;
use App\Http\Controllers\Brand\ContractController;
use App\Http\Controllers\Brand\DiscoveryController;
use App\Http\Controllers\Brand\OnboardingController;
use App\Http\Controllers\Brand\ProfileEditorController;
use App\Http\Controllers\Brand\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Brand dashboard (brand guard)
|--------------------------------------------------------------------------
|
| Loaded inside auth:brand + prefix('brand') + name('brand.'). Page routes (GET)
| return Blade shells; every other action returns the shared JSON envelope for
| the http.js/Alpine front-end (no page reloads).
|
*/

// --- Onboarding wizard (6 steps) --------------------------------------------
Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding');
Route::post('/onboarding/identity', [OnboardingController::class, 'identity'])->name('onboarding.identity');
Route::post('/onboarding/location', [OnboardingController::class, 'location'])->name('onboarding.location');
Route::post('/onboarding/creative-needs', [OnboardingController::class, 'creativeNeeds'])->name('onboarding.creative-needs');
Route::post('/onboarding/aesthetic', [OnboardingController::class, 'aesthetic'])->name('onboarding.aesthetic');
Route::post('/onboarding/budget', [OnboardingController::class, 'budget'])->name('onboarding.budget');
Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

// --- Dashboard home ---------------------------------------------------------
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// --- Profile editor ---------------------------------------------------------
Route::get('/profile', [ProfileEditorController::class, 'edit'])->name('profile');
Route::patch('/profile', [ProfileEditorController::class, 'updateCore'])->name('profile.update');
Route::post('/profile/logo', [ProfileEditorController::class, 'uploadLogo'])->name('profile.logo');
Route::post('/profile/cover', [ProfileEditorController::class, 'uploadCover'])->name('profile.cover');
Route::patch('/profile/aesthetic', [ProfileEditorController::class, 'updateAesthetic'])->name('profile.aesthetic');
Route::get('/profile/images', [ProfileEditorController::class, 'images'])->name('profile.images');
Route::post('/profile/images', [ProfileEditorController::class, 'addImage'])->name('profile.images.store');
Route::delete('/profile/images/{image}', [ProfileEditorController::class, 'removeImage'])->name('profile.images.destroy');
Route::get('/social/data', [ProfileEditorController::class, 'socialData'])->name('social.data');
Route::post('/social', [ProfileEditorController::class, 'addSocial'])->name('social.store');
Route::delete('/social/{handle}', [ProfileEditorController::class, 'removeSocial'])->name('social.destroy');

// --- Creative needs / preferences -------------------------------------------
Route::get('/creative-needs', [CreativeNeedsController::class, 'edit'])->name('creative-needs');
Route::patch('/creative-needs', [CreativeNeedsController::class, 'update'])->name('creative-needs.update');

// --- Projects manager + workspace ------------------------------------------
Route::get('/projects', [ProjectController::class, 'index'])->name('projects');
Route::get('/projects/data', [ProjectController::class, 'data'])->name('projects.data');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{campaign}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/projects/{campaign}/data', [ProjectController::class, 'showData'])->name('projects.show.data');
Route::patch('/projects/{campaign}', [ProjectController::class, 'update'])->name('projects.update');
Route::patch('/projects/{campaign}/status', [ProjectController::class, 'status'])->name('projects.status');
Route::patch('/projects/{campaign}/public', [ProjectController::class, 'setPublic'])->name('projects.public');
Route::post('/projects/{campaign}/media', [ProjectController::class, 'addMedia'])->name('projects.media');
Route::delete('/projects/{campaign}/media/{media}', [ProjectController::class, 'removeMedia'])->name('projects.media.destroy');
Route::delete('/projects/{campaign}', [ProjectController::class, 'destroy'])->name('projects.destroy');

// --- Discovery feed ---------------------------------------------------------
Route::get('/discover', [DiscoveryController::class, 'index'])->name('discover');
Route::get('/discover/feed', [DiscoveryController::class, 'feed'])->name('discover.feed');
Route::post('/discover/save', [DiscoveryController::class, 'save'])->name('discover.save');
Route::post('/discover/brief', [DiscoveryController::class, 'brief'])->name('discover.brief');

// --- Contracts inbox + brand contract room (shared engine, brand actor) -------------
Route::get('/contracts', [ContractController::class, 'index'])->name('contracts');
Route::get('/contracts/data', [ContractController::class, 'data'])->name('contracts.data');
Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
Route::get('/contracts/{contract}/thread', [ContractController::class, 'thread'])->name('contracts.thread');
Route::post('/contracts/{contract}/advance', [ContractController::class, 'advance'])->name('contracts.advance');
Route::post('/contracts/{contract}/reject', [ContractController::class, 'reject'])->name('contracts.reject');
Route::post('/contracts/{contract}/skip', [ContractController::class, 'skip'])->name('contracts.skip');
Route::post('/contracts/{contract}/message', [ContractController::class, 'message'])->name('contracts.message');

// --- Reviews received (read-only) -------------------------------------------
Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews');
Route::get('/reviews/data', [ReviewController::class, 'data'])->name('reviews.data');

// --- Account / settings -----------------------------------------------------
Route::get('/account', [AccountController::class, 'index'])->name('account');
Route::patch('/account', [AccountController::class, 'update'])->name('account.update');
Route::patch('/account/publish', [AccountController::class, 'publish'])->name('account.publish');
