<?php

use App\Http\Controllers\Talent\ApplicationController;
use App\Http\Controllers\Talent\BlockContentController;
use App\Http\Controllers\Talent\DashboardController;
use App\Http\Controllers\Talent\ContractController;
use App\Http\Controllers\Talent\ProfileEditorController;
use App\Http\Controllers\Talent\ReviewController;
use App\Http\Controllers\Talent\SkillController;
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
| The sidebar is: Home · Profile · Content · Reviews · Contracts. The Profile editor
| is the single profile surface — identity, Skills, username, publish, pricing
| rate, and the reorderable blocks (the old Professions + Account tabs folded in).
|
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// --- Profile editor (the single profile surface) ----------------------------
Route::get('/profile', [ProfileEditorController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileEditorController::class, 'updateCore'])->name('profile.update');
Route::post('/profile/avatar', [ProfileEditorController::class, 'updateAvatar'])->name('profile.avatar.update');
Route::delete('/profile/avatar', [ProfileEditorController::class, 'removeAvatar'])->name('profile.avatar.destroy');
Route::patch('/profile/pricing', [ProfileEditorController::class, 'updatePricingRate'])->name('profile.pricing');
Route::patch('/profile/publish', [ProfileEditorController::class, 'publish'])->name('profile.publish');
Route::get('/profile/blocks', [ProfileEditorController::class, 'blocks'])->name('profile.blocks');
Route::get('/profile/block-picker', [ProfileEditorController::class, 'picker'])->name('profile.picker');
Route::post('/profile/blocks', [ProfileEditorController::class, 'addBlock'])->name('profile.blocks.store');
Route::patch('/profile/blocks/reorder', [ProfileEditorController::class, 'reorderBlocks'])->name('profile.blocks.reorder');
Route::patch('/profile/blocks/{block}/move', [ProfileEditorController::class, 'moveBlock'])->name('profile.blocks.move');
Route::patch('/profile/blocks/{block}', [ProfileEditorController::class, 'fillBlock'])->name('profile.blocks.update');
Route::patch('/profile/blocks/{block}/visibility', [ProfileEditorController::class, 'toggleBlock'])->name('profile.blocks.visibility');
Route::delete('/profile/blocks/{block}', [ProfileEditorController::class, 'removeBlock'])->name('profile.blocks.destroy');

// --- Skills (a section inside the Profile editor) ---------------------------
Route::get('/profile/skills', [SkillController::class, 'data'])->name('profile.skills.data');
Route::post('/profile/skills', [SkillController::class, 'store'])->name('profile.skills.store');
Route::patch('/profile/skills/reorder', [SkillController::class, 'reorder'])->name('profile.skills.reorder');
Route::patch('/profile/skills/{type}/primary', [SkillController::class, 'primary'])->name('profile.skills.primary');
Route::delete('/profile/skills/{type}', [SkillController::class, 'destroy'])->name('profile.skills.destroy');

// --- Reviews moderation -----------------------------------------------------
Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews');
Route::get('/reviews/data', [ReviewController::class, 'data'])->name('reviews.data');
Route::patch('/reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
Route::patch('/reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');

// --- Applications (talent applies to a brand project) -----------------------
Route::get('/applications/mentions', [ApplicationController::class, 'mentions'])->name('applications.mentions');
Route::post('/applications/{brandProject}', [ApplicationController::class, 'store'])->name('applications.store');

// --- Contract room + inbox ------------------------------------------------------
Route::get('/contracts', [ContractController::class, 'index'])->name('contracts');
Route::get('/contracts/data', [ContractController::class, 'data'])->name('contracts.data');
Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
Route::get('/contracts/{contract}/thread', [ContractController::class, 'thread'])->name('contracts.thread');
Route::post('/contracts/{contract}/advance', [ContractController::class, 'advance'])->name('contracts.advance');
Route::post('/contracts/{contract}/reject', [ContractController::class, 'reject'])->name('contracts.reject');
Route::post('/contracts/{contract}/skip', [ContractController::class, 'skip'])->name('contracts.skip');
Route::post('/contracts/{contract}/message', [ContractController::class, 'message'])->name('contracts.message');

// --- Block content editors (child tables; content_source-aware) -------------
Route::get('/content/{type}', [BlockContentController::class, 'index'])->name('content');
Route::get('/content/{type}/data', [BlockContentController::class, 'data'])->name('content.data');
Route::post('/content/{type}', [BlockContentController::class, 'store'])->name('content.store');
Route::patch('/content/{type}/reorder', [BlockContentController::class, 'reorder'])->name('content.reorder');
Route::patch('/content/{type}/{id}', [BlockContentController::class, 'update'])->name('content.update');
Route::delete('/content/{type}/{id}', [BlockContentController::class, 'destroy'])->name('content.destroy');
Route::post('/content/{type}/{id}/media', [BlockContentController::class, 'uploadMedia'])->name('content.media');
