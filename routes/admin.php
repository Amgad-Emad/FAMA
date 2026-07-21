<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\BlockCatalogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ContractInterventionController;
use App\Http\Controllers\Admin\FlowBuilderController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\SkillController;
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

// --- Contract-flow builder (manage-flows) ---------------------------------------
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

    // Skill/template catalog rides on the same authoring permission.
    Route::get('/skills', [SkillController::class, 'index'])->name('skills');
    Route::get('/skills/data', [SkillController::class, 'data'])->name('skills.data');
    Route::post('/skills', [SkillController::class, 'store'])->name('skills.store');
    Route::patch('/skills/{type}/blocks', [SkillController::class, 'updateBlocks'])->name('skills.blocks');
});

// --- Block catalog (manage-blocks) -------------------------------------------
// Owns block-type existence + eligibility (availability/gates) + config; the
// Skills page only picks preselection/order among what this catalog allows.
Route::middleware('can:manage-blocks')->group(function () {
    Route::get('/blocks', [BlockCatalogController::class, 'index'])->name('blocks');
    Route::get('/blocks/data', [BlockCatalogController::class, 'data'])->name('blocks.data');
    Route::post('/blocks', [BlockCatalogController::class, 'store'])->name('blocks.store');
    Route::patch('/blocks/{blockType}', [BlockCatalogController::class, 'update'])->name('blocks.update');
    Route::patch('/blocks/{blockType}/toggle', [BlockCatalogController::class, 'toggle'])->name('blocks.toggle');
});

// --- Moderation queues (moderate-content) -----------------------------------
Route::middleware('can:moderate-content')->prefix('moderation')->name('moderation.')->group(function () {
    Route::get('/', [ModerationController::class, 'index'])->name('index');

    Route::get('/talents', [ModerationController::class, 'talents'])->name('talents');
    Route::get('/talents/{talent}', [ModerationController::class, 'showTalent'])->withTrashed()->name('talents.show');
    Route::patch('/talents/{talent}/{action}', [ModerationController::class, 'moderateTalent'])
        ->withTrashed()->name('talents.action');

    // The global queue must register before the {review} action routes.
    Route::get('/all-reviews', [ModerationController::class, 'allReviews'])->name('all-reviews');

    Route::get('/reviews', [ModerationController::class, 'reviews'])->name('reviews');
    Route::get('/reviews/{review}', [ModerationController::class, 'showReview'])->name('reviews.show');
    Route::patch('/reviews/{review}/{action}', [ModerationController::class, 'moderateReview'])->name('reviews.action');
    Route::post('/reviews/batch', [ModerationController::class, 'batchReviews'])->name('reviews.batch');

    Route::get('/brands', [ModerationController::class, 'brands'])->name('brands');
    Route::get('/brands/{brand}', [ModerationController::class, 'showBrand'])->withTrashed()->name('brands.show');
    Route::patch('/brands/{brand}/{action}', [ModerationController::class, 'moderateBrand'])->name('brands.action');

    Route::get('/brand-reviews', [ModerationController::class, 'brandReviews'])->name('brand-reviews');
    Route::get('/brand-reviews/{review}', [ModerationController::class, 'showBrandReview'])->name('brand-reviews.show');
    Route::patch('/brand-reviews/{review}/{action}', [ModerationController::class, 'moderateBrandReview'])->name('brand-reviews.action');

    Route::get('/projects', [ModerationController::class, 'projects'])->name('projects');
    Route::get('/projects/{project}', [ModerationController::class, 'showProject'])->withTrashed()->name('projects.show');
    Route::patch('/projects/{project}/{action}', [ModerationController::class, 'moderateProject'])->name('projects.action');
});

// --- Contract intervention console (intervene-contracts) ----------------------------
Route::middleware('can:intervene-contracts')->group(function () {
    Route::get('/contracts', [ContractInterventionController::class, 'index'])->name('contracts');
    Route::get('/contracts/data', [ContractInterventionController::class, 'data'])->name('contracts.data');
    Route::get('/contracts/{contract}', [ContractInterventionController::class, 'show'])->name('contracts.show');
    Route::get('/contracts/{contract}/thread', [ContractInterventionController::class, 'thread'])->name('contracts.thread');
    Route::post('/contracts/{contract}/{action}', [ContractInterventionController::class, 'act'])->name('contracts.act');
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
