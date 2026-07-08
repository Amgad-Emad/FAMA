<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DealInterventionController;
use App\Http\Controllers\Admin\FlowBuilderController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\ProfessionController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin dashboard (admin guard)
|--------------------------------------------------------------------------
|
| Loaded inside auth:admin + prefix('admin') + name('admin.'). Page access is
| gated per capability with `can:` middleware (spatie permissions on the admin
| guard). GET page routes return Blade shells; everything else returns the JSON
| envelope for the Alpine/http.js front-end (no reloads).
|
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// --- Deal-flow builder (manage-flows) ---------------------------------------
Route::middleware('can:manage-flows')->group(function () {
    Route::get('/flows', [FlowBuilderController::class, 'index'])->name('flows');
    Route::get('/flows/data', [FlowBuilderController::class, 'data'])->name('flows.data');
    Route::post('/flows', [FlowBuilderController::class, 'store'])->name('flows.store');
    Route::get('/flows/{flow}', [FlowBuilderController::class, 'show'])->name('flows.show');
    Route::get('/flows/{flow}/data', [FlowBuilderController::class, 'showData'])->name('flows.show.data');
    Route::patch('/flows/{flow}', [FlowBuilderController::class, 'update'])->name('flows.update');
    Route::patch('/flows/{flow}/default', [FlowBuilderController::class, 'markDefault'])->name('flows.default');
    Route::patch('/flows/{flow}/activate', [FlowBuilderController::class, 'activate'])->name('flows.activate');
    Route::patch('/flows/{flow}/archive', [FlowBuilderController::class, 'archive'])->name('flows.archive');
    Route::post('/flows/{flow}/steps', [FlowBuilderController::class, 'addStep'])->name('flows.steps.store');
    Route::patch('/flows/{flow}/steps/reorder', [FlowBuilderController::class, 'reorderSteps'])->name('flows.steps.reorder');
    Route::patch('/flows/{flow}/steps/{step}', [FlowBuilderController::class, 'updateStep'])->name('flows.steps.update');
    Route::delete('/flows/{flow}/steps/{step}', [FlowBuilderController::class, 'removeStep'])->name('flows.steps.destroy');

    // Profession/template catalog rides on the same authoring permission.
    Route::get('/professions', [ProfessionController::class, 'index'])->name('professions');
    Route::get('/professions/data', [ProfessionController::class, 'data'])->name('professions.data');
    Route::post('/professions', [ProfessionController::class, 'store'])->name('professions.store');
    Route::patch('/professions/{type}/blocks', [ProfessionController::class, 'updateBlocks'])->name('professions.blocks');
});

// --- Moderation queues (moderate-content) -----------------------------------
Route::middleware('can:moderate-content')->prefix('moderation')->name('moderation.')->group(function () {
    Route::get('/', [ModerationController::class, 'index'])->name('index');

    Route::get('/talents', [ModerationController::class, 'talents'])->name('talents');
    Route::patch('/talents/{talent}/{action}', [ModerationController::class, 'moderateTalent'])
        ->withTrashed()->name('talents.action');

    Route::get('/reviews', [ModerationController::class, 'reviews'])->name('reviews');
    Route::patch('/reviews/{review}/{action}', [ModerationController::class, 'moderateReview'])->name('reviews.action');
    Route::post('/reviews/batch', [ModerationController::class, 'batchReviews'])->name('reviews.batch');

    Route::get('/brands', [ModerationController::class, 'brands'])->name('brands');
    Route::patch('/brands/{brand}/{action}', [ModerationController::class, 'moderateBrand'])->name('brands.action');

    Route::get('/brand-reviews', [ModerationController::class, 'brandReviews'])->name('brand-reviews');
    Route::patch('/brand-reviews/{review}/{action}', [ModerationController::class, 'moderateBrandReview'])->name('brand-reviews.action');

    Route::get('/campaigns', [ModerationController::class, 'campaigns'])->name('campaigns');
    Route::patch('/campaigns/{campaign}/{action}', [ModerationController::class, 'moderateCampaign'])->name('campaigns.action');
});

// --- Deal intervention console (intervene-deals) ----------------------------
Route::middleware('can:intervene-deals')->group(function () {
    Route::get('/deals', [DealInterventionController::class, 'index'])->name('deals');
    Route::get('/deals/data', [DealInterventionController::class, 'data'])->name('deals.data');
    Route::get('/deals/{deal}', [DealInterventionController::class, 'show'])->name('deals.show');
    Route::get('/deals/{deal}/thread', [DealInterventionController::class, 'thread'])->name('deals.thread');
    Route::post('/deals/{deal}/{action}', [DealInterventionController::class, 'act'])->name('deals.act');
});

// --- Activity log + settings (manage-settings) ------------------------------
Route::middleware('can:manage-settings')->group(function () {
    Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity');
    Route::get('/activity/data', [ActivityLogController::class, 'data'])->name('activity.data');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

// --- Admin users (manage-users) ---------------------------------------------
Route::middleware('can:manage-users')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users');
    Route::get('/users/data', [AdminUserController::class, 'data'])->name('users.data');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/roles', [AdminUserController::class, 'syncRoles'])->name('users.roles');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
});
