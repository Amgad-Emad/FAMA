<?php

use App\Http\Controllers\Brand\AccountController;
use App\Http\Controllers\Brand\CampaignController;
use App\Http\Controllers\Brand\CreativeNeedsController;
use App\Http\Controllers\Brand\DashboardController;
use App\Http\Controllers\Brand\DealController;
use App\Http\Controllers\Brand\DiscoveryController;
use App\Http\Controllers\Brand\EnquiryController;
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

// --- Campaigns manager + workspace ------------------------------------------
Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns');
Route::get('/campaigns/data', [CampaignController::class, 'data'])->name('campaigns.data');
Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
Route::get('/campaigns/{campaign}/data', [CampaignController::class, 'showData'])->name('campaigns.show.data');
Route::patch('/campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update');
Route::patch('/campaigns/{campaign}/status', [CampaignController::class, 'status'])->name('campaigns.status');
Route::patch('/campaigns/{campaign}/public', [CampaignController::class, 'setPublic'])->name('campaigns.public');
Route::post('/campaigns/{campaign}/media', [CampaignController::class, 'addMedia'])->name('campaigns.media');
Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');

// --- Discovery feed ---------------------------------------------------------
Route::get('/discover', [DiscoveryController::class, 'index'])->name('discover');
Route::get('/discover/feed', [DiscoveryController::class, 'feed'])->name('discover.feed');
Route::post('/discover/save', [DiscoveryController::class, 'save'])->name('discover.save');
Route::post('/discover/brief', [DiscoveryController::class, 'brief'])->name('discover.brief');

// --- Deal initiation (Path A: "Start a deal") + Path B: pending enquiries ---
Route::post('/deals', [DealController::class, 'store'])->name('deals.store');
Route::get('/enquiries', [EnquiryController::class, 'index'])->name('enquiries');
Route::get('/enquiries/data', [EnquiryController::class, 'data'])->name('enquiries.data');
Route::post('/enquiries/{enquiry}/convert', [EnquiryController::class, 'convert'])->name('enquiries.convert');

// --- Deals inbox + brand deal room (shared engine, brand actor) -------------
Route::get('/deals', [DealController::class, 'index'])->name('deals');
Route::get('/deals/data', [DealController::class, 'data'])->name('deals.data');
Route::get('/deals/{deal}', [DealController::class, 'show'])->name('deals.show');
Route::get('/deals/{deal}/thread', [DealController::class, 'thread'])->name('deals.thread');
Route::post('/deals/{deal}/advance', [DealController::class, 'advance'])->name('deals.advance');
Route::post('/deals/{deal}/reject', [DealController::class, 'reject'])->name('deals.reject');
Route::post('/deals/{deal}/skip', [DealController::class, 'skip'])->name('deals.skip');
Route::post('/deals/{deal}/message', [DealController::class, 'message'])->name('deals.message');

// --- Reviews received (read-only) -------------------------------------------
Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews');
Route::get('/reviews/data', [ReviewController::class, 'data'])->name('reviews.data');

// --- Account / settings -----------------------------------------------------
Route::get('/account', [AccountController::class, 'index'])->name('account');
Route::patch('/account', [AccountController::class, 'update'])->name('account.update');
Route::patch('/account/publish', [AccountController::class, 'publish'])->name('account.publish');
