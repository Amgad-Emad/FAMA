<?php

use App\Http\Controllers\Talent\ApplicationController;
use App\Http\Controllers\Talent\BlockContentController;
use App\Http\Controllers\Talent\ContractController;
use App\Http\Controllers\Talent\CreditController;
use App\Http\Controllers\Talent\DashboardController;
use App\Http\Controllers\Talent\InterestController;
use App\Http\Controllers\Talent\ProfileEditorController;
use App\Http\Controllers\Talent\ProjectController;
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
| rate, and the reorderable blocks (the old Skills + Account tabs folded in).
|
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/views', [DashboardController::class, 'viewStats'])->name('dashboard.views');

// --- Profile editor (the single profile surface) ----------------------------
Route::get('/profile', [ProfileEditorController::class, 'edit'])->name('profile.edit');
Route::patch('/profile', [ProfileEditorController::class, 'updateCore'])->name('profile.update');
Route::post('/profile/avatar', [ProfileEditorController::class, 'updateAvatar'])->name('profile.avatar.update');
Route::delete('/profile/avatar', [ProfileEditorController::class, 'removeAvatar'])->name('profile.avatar.destroy');
Route::patch('/profile/pricing', [ProfileEditorController::class, 'updatePricingRate'])->name('profile.pricing')->middleware('feature:pricing');
Route::patch('/profile/publish', [ProfileEditorController::class, 'publish'])->name('profile.publish');
Route::get('/profile/state', [ProfileEditorController::class, 'state'])->name('profile.state');
Route::patch('/profile/sections/universal-position', [ProfileEditorController::class, 'updateSectionOrder'])->name('profile.sections.universal');
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
Route::put('/profile/skills/{type}/specialities', [SkillController::class, 'specialities'])->name('profile.skills.specialities');
Route::delete('/profile/skills/{type}', [SkillController::class, 'destroy'])->name('profile.skills.destroy');

// --- Now + About (ADR-Z: the two time-sensitive toggles, and social links) ----
Route::patch('/profile/now', [ProfileEditorController::class, 'updateNow'])->name('profile.now');
Route::put('/profile/social', [ProfileEditorController::class, 'updateSocial'])->name('profile.social');

// --- Willing to explore (a section inside the Profile editor) ----------------
Route::get('/profile/interests', [InterestController::class, 'data'])->name('profile.interests.data');
Route::post('/profile/interests', [InterestController::class, 'store'])->name('profile.interests.store');
Route::patch('/profile/interests/reorder', [InterestController::class, 'reorder'])->name('profile.interests.reorder');
Route::patch('/profile/interests/{interest}', [InterestController::class, 'update'])->name('profile.interests.update');
Route::delete('/profile/interests/{interest}', [InterestController::class, 'destroy'])->name('profile.interests.destroy');

// --- Projects (the talent's own portfolio of work) ---------------------------
// A project carries at least one skill (ADR-X) — the tags decide which skill
// tabs it appears in on the public profile.
Route::get('/projects', [ProjectController::class, 'index'])->name('projects');
Route::get('/projects/data', [ProjectController::class, 'data'])->name('projects.data');
// The editor lives on its own page (fama-ui project-edit) — new + edit; both submit
// to the JSON endpoints below. `new` is registered before the {project} routes.
Route::get('/projects/new', [ProjectController::class, 'create'])->name('projects.new');
Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::patch('/projects/reorder', [ProjectController::class, 'reorder'])->name('projects.reorder');
Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
// Co-ownership: any owner may leave (a co-owner withdraws; the creator hands it on).
Route::post('/projects/{project}/leave', [ProjectController::class, 'leave'])->name('projects.leave');
Route::post('/projects/{project}/feature', [ProjectController::class, 'feature'])->name('projects.feature');
Route::post('/projects/{project}/cover', [ProjectController::class, 'uploadCover'])->name('projects.cover.store');
Route::delete('/projects/{project}/cover', [ProjectController::class, 'destroyCover'])->name('projects.cover.destroy');

// The work itself — an ordered media gallery (ADR-AA). The ORDER is the edit.
Route::post('/projects/{project}/media', [ProjectController::class, 'storeMedia'])->name('projects.media.store');
Route::patch('/projects/{project}/media/reorder', [ProjectController::class, 'reorderMedia'])->name('projects.media.reorder');
Route::delete('/projects/{project}/media/{media}', [ProjectController::class, 'destroyMedia'])
    ->whereNumber('media')->name('projects.media.destroy');

// Collaborator credits (ADR-AA) — the OWNER's side: add, reorder, remove.
Route::post('/projects/{project}/credits', [CreditController::class, 'store'])->name('projects.credits.store');
Route::patch('/projects/{project}/credits/reorder', [CreditController::class, 'reorder'])->name('projects.credits.reorder');
Route::delete('/projects/{project}/credits/{credit}', [CreditController::class, 'destroy'])->name('projects.credits.destroy');

// … and the CREDITED PERSON's side: confirm or refuse a credit naming you.
// Separate authorisation — owning a project never lets you answer for someone else.
Route::get('/credits', [CreditController::class, 'index'])->name('credits');
Route::get('/credits/data', [CreditController::class, 'data'])->name('credits.data');
Route::patch('/credits/{credit}/{action}', [CreditController::class, 'respond'])
    ->whereIn('action', ['accept', 'decline'])->name('credits.respond');

// --- Reviews moderation -----------------------------------------------------
// Consolidated "Activity" page — Saved · Liked · Following in one tabbed surface.
Route::get('/feed/activity', [\App\Http\Controllers\Talent\FeedActivityController::class, 'index'])->name('feed.activity');

// Feed collections (5X, ADR-F9 — private to the owner). The page routes now
// redirect into the Activity tabs; the Ajax data endpoints are unchanged.
Route::get('/feed/saved', fn () => redirect()->route('talent.feed.activity', ['view' => 'saved']))->name('feed.saved');
Route::get('/feed/liked', fn () => redirect()->route('talent.feed.activity', ['view' => 'liked']))->name('feed.liked');
Route::get('/feed/collection/data', [\App\Http\Controllers\Talent\FeedCollectionController::class, 'data'])->name('feed.collection.data');

// Follow-graph management (FOLLOWING / FOLLOWERS sub-tabs) → the Following tab.
Route::get('/feed/follows', fn (\Illuminate\Http\Request $r) => redirect()->route('talent.feed.activity', ['view' => 'following'] + ($r->query('tab') ? ['tab' => $r->query('tab')] : [])))->name('feed.follows');
Route::get('/feed/follows/data', [\App\Http\Controllers\Talent\FollowsController::class, 'data'])->name('feed.follows.data');

Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews');
Route::get('/reviews/data', [ReviewController::class, 'data'])->name('reviews.data');
Route::patch('/reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
Route::patch('/reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');

// --- Applications + contract room/inbox (talent side of the marketplace) -----
// Gated behind the `contracts` feature flag for the V1 release.
Route::middleware('feature:contracts')->group(function () {
    // Applications (talent applies to a brand project).
    Route::get('/applications/mentions', [ApplicationController::class, 'mentions'])->name('applications.mentions');
    Route::post('/applications/{brandProject}', [ApplicationController::class, 'store'])->name('applications.store');

    // Contract room + inbox.
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts');
    Route::get('/contracts/data', [ContractController::class, 'data'])->name('contracts.data');
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('/contracts/{contract}/thread', [ContractController::class, 'thread'])->name('contracts.thread');
    Route::get('/contracts/{contract}/document', [ContractController::class, 'document'])->name('contracts.document');
    Route::get('/contracts/{contract}/document.pdf', [ContractController::class, 'documentPdf'])->name('contracts.document.pdf');
    Route::post('/contracts/{contract}/advance', [ContractController::class, 'advance'])->name('contracts.advance');
    Route::post('/contracts/{contract}/reject', [ContractController::class, 'reject'])->name('contracts.reject');
    Route::post('/contracts/{contract}/skip', [ContractController::class, 'skip'])->name('contracts.skip');
    Route::post('/contracts/{contract}/message', [ContractController::class, 'message'])->name('contracts.message');
});

// --- Block content editors (child tables; content_source-aware) -------------
// The page is SECTION-based ({section} = `universal` or a skill slug); a legacy
// content-type key still resolves to the section that shows it.
Route::get('/content/{section?}', [BlockContentController::class, 'index'])->name('content');
Route::get('/content/{type}/data', [BlockContentController::class, 'data'])->name('content.data');
Route::post('/content/{type}', [BlockContentController::class, 'store'])->name('content.store');
Route::patch('/content/{type}/reorder', [BlockContentController::class, 'reorder'])->name('content.reorder');
Route::patch('/content/{type}/{id}', [BlockContentController::class, 'update'])->name('content.update');
Route::delete('/content/{type}/{id}', [BlockContentController::class, 'destroy'])->name('content.destroy');
Route::post('/content/{type}/{id}/media', [BlockContentController::class, 'uploadMedia'])->name('content.media');
