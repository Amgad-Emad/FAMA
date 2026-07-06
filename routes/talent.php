<?php

use App\Http\Controllers\Talent\AccountController;
use App\Http\Controllers\Talent\AffiliationController;
use App\Http\Controllers\Talent\AvailabilityController;
use App\Http\Controllers\Talent\BlockContentController;
use App\Http\Controllers\Talent\DashboardController;
use App\Http\Controllers\Talent\PressController;
use App\Http\Controllers\Talent\ProfessionController;
use App\Http\Controllers\Talent\ProfileEditorController;
use App\Http\Controllers\Talent\ReviewController;
use App\Http\Controllers\Talent\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Talent dashboard (talent guard)
|--------------------------------------------------------------------------
|
| Loaded inside the auth:talent + prefix('talent') + name('talent.') group.
| Page routes (GET) return Blade shells; every other action returns the shared
| JSON envelope for the http.js/Alpine front-end (no page reloads).
|
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// --- Profile editor ---------------------------------------------------------
Route::get('/profile', [ProfileEditorController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileEditorController::class, 'updateCore'])->name('profile.update');
Route::post('/profile/hero', [ProfileEditorController::class, 'uploadHero'])->name('profile.hero');
Route::get('/profile/blocks', [ProfileEditorController::class, 'blocks'])->name('profile.blocks');
Route::get('/profile/block-picker', [ProfileEditorController::class, 'picker'])->name('profile.picker');
Route::post('/profile/blocks', [ProfileEditorController::class, 'addBlock'])->name('profile.blocks.store');
Route::patch('/profile/blocks/reorder', [ProfileEditorController::class, 'reorderBlocks'])->name('profile.blocks.reorder');
Route::patch('/profile/blocks/{block}', [ProfileEditorController::class, 'fillBlock'])->name('profile.blocks.update');
Route::patch('/profile/blocks/{block}/visibility', [ProfileEditorController::class, 'toggleBlock'])->name('profile.blocks.visibility');
Route::delete('/profile/blocks/{block}', [ProfileEditorController::class, 'removeBlock'])->name('profile.blocks.destroy');

// --- Professions ------------------------------------------------------------
Route::get('/professions', [ProfessionController::class, 'index'])->name('professions');
Route::get('/professions/data', [ProfessionController::class, 'data'])->name('professions.data');
Route::post('/professions', [ProfessionController::class, 'store'])->name('professions.store');
Route::patch('/professions/reorder', [ProfessionController::class, 'reorder'])->name('professions.reorder');
Route::patch('/professions/{type}/primary', [ProfessionController::class, 'primary'])->name('professions.primary');
Route::delete('/professions/{type}', [ProfessionController::class, 'destroy'])->name('professions.destroy');

// --- Services / rate card ---------------------------------------------------
Route::get('/services', [ServiceController::class, 'index'])->name('services');
Route::get('/services/data', [ServiceController::class, 'data'])->name('services.data');
Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
Route::patch('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');
Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

// --- Availability & travel --------------------------------------------------
Route::get('/availability', [AvailabilityController::class, 'index'])->name('availability');
Route::patch('/availability', [AvailabilityController::class, 'update'])->name('availability.update');

// --- Reviews moderation -----------------------------------------------------
Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews');
Route::get('/reviews/data', [ReviewController::class, 'data'])->name('reviews.data');
Route::patch('/reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
Route::patch('/reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');

// --- Affiliations & press ---------------------------------------------------
Route::get('/affiliations', [AffiliationController::class, 'index'])->name('affiliations');
Route::get('/affiliations/data', [AffiliationController::class, 'data'])->name('affiliations.data');
Route::post('/affiliations', [AffiliationController::class, 'store'])->name('affiliations.store');
Route::patch('/affiliations/{affiliation}', [AffiliationController::class, 'update'])->name('affiliations.update');
Route::patch('/affiliations/{affiliation}/end', [AffiliationController::class, 'end'])->name('affiliations.end');
Route::delete('/affiliations/{affiliation}', [AffiliationController::class, 'destroy'])->name('affiliations.destroy');
Route::get('/press/data', [PressController::class, 'data'])->name('press.data');
Route::post('/press', [PressController::class, 'store'])->name('press.store');
Route::delete('/press/{press}', [PressController::class, 'destroy'])->name('press.destroy');

// --- Account / settings -----------------------------------------------------
Route::get('/account', [AccountController::class, 'index'])->name('account');
Route::patch('/account', [AccountController::class, 'update'])->name('account.update');
Route::patch('/account/publish', [AccountController::class, 'publish'])->name('account.publish');

// --- Block content editors (child tables; content_source-aware) -------------
Route::get('/content/{type}', [BlockContentController::class, 'index'])->name('content');
Route::get('/content/{type}/data', [BlockContentController::class, 'data'])->name('content.data');
Route::post('/content/{type}', [BlockContentController::class, 'store'])->name('content.store');
Route::patch('/content/{type}/reorder', [BlockContentController::class, 'reorder'])->name('content.reorder');
Route::patch('/content/{type}/{id}', [BlockContentController::class, 'update'])->name('content.update');
Route::delete('/content/{type}/{id}', [BlockContentController::class, 'destroy'])->name('content.destroy');
Route::post('/content/{type}/{id}/media', [BlockContentController::class, 'uploadMedia'])->name('content.media');
