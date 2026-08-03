<?php

use App\Http\Controllers\Feed\CommentController;
use App\Http\Controllers\Feed\FeedController;
use App\Http\Controllers\Feed\FollowController;
use App\Http\Controllers\Feed\FollowListController;
use App\Http\Controllers\Feed\LikeController;
use App\Http\Controllers\Feed\LinkPreviewController;
use App\Http\Controllers\Feed\MentionSearchController;
use App\Http\Controllers\Feed\NotificationController;
use App\Http\Controllers\Feed\PostController;
use App\Http\Controllers\Feed\ProjectShareController;
use App\Http\Controllers\Feed\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API v1 — the feed mirror (Prompt 5K, feed-spec §12)
|--------------------------------------------------------------------------
|
| Sanctum tokens (talents + brands). The SAME controllers, form requests,
| actions and Resources as the web Ajax layer — the two can never drift.
| Reads are public (guests welcome, ADR-F8); writes require an Actor
| (require.actor answers 401 envelopes; there is no session, so no intent
| replay). The full platform API (profiles, contracts, …) is Phase 4.
|
*/

Route::prefix('v1')->middleware('resolve.actor')->group(function () {

    // Feed stream + casting strip
    Route::get('/feed/items', [FeedController::class, 'items']);
    Route::get('/feed/casting', [FeedController::class, 'casting']);

    // Posts
    Route::get('/posts/{post}', [PostController::class, 'apiShow'])->whereNumber('post');
    Route::get('/talents/{username}/posts', [PostController::class, 'byTalent']);
    Route::post('/posts', [PostController::class, 'store'])
        ->middleware(['auth:sanctum', 'require.actor:post', 'throttle:feed-posts']);
    Route::patch('/posts/{post}', [PostController::class, 'update'])
        ->middleware(['auth:sanctum', 'require.actor:post']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])
        ->middleware(['auth:sanctum', 'require.actor:post']);
    Route::post('/posts/{post}/pin', [PostController::class, 'pin'])
        ->middleware(['auth:sanctum', 'require.actor:post']);
    Route::post('/projects/{project}/share', ProjectShareController::class)
        ->middleware(['auth:sanctum', 'require.actor:share']);

    // Comments
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->middleware(['auth:sanctum', 'require.actor:comment', 'throttle:feed-comments']);
    Route::get('/comments/{comment}/replies', [CommentController::class, 'replies']);
    Route::patch('/comments/{comment}', [CommentController::class, 'update'])
        ->middleware(['auth:sanctum', 'require.actor:comment']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->middleware(['auth:sanctum', 'require.actor:comment']);

    // Likes
    Route::post('/posts/{post}/like', [LikeController::class, 'store'])
        ->middleware(['auth:sanctum', 'require.actor:like', 'throttle:feed-likes']);
    Route::delete('/posts/{post}/like', [LikeController::class, 'destroy'])
        ->middleware(['auth:sanctum', 'require.actor:like', 'throttle:feed-likes']);
    Route::post('/comments/{comment}/like', [LikeController::class, 'storeComment'])
        ->middleware(['auth:sanctum', 'require.actor:like', 'throttle:feed-likes']);
    Route::delete('/comments/{comment}/like', [LikeController::class, 'destroyComment'])
        ->middleware(['auth:sanctum', 'require.actor:like', 'throttle:feed-likes']);

    // Follow-suggestion rails (5Z)
    Route::get('/feed/suggestions/talents', [\App\Http\Controllers\Feed\SuggestionController::class, 'talents']);
    Route::get('/feed/suggestions/brands', [\App\Http\Controllers\Feed\SuggestionController::class, 'brands']);
    Route::post('/feed/suggestions/dismiss', [\App\Http\Controllers\Feed\SuggestionController::class, 'dismiss'])
        ->middleware(['auth:sanctum', 'require.actor:dismiss', 'throttle:feed-dismissals']);

    // Saves (5X)
    Route::post('/posts/{post}/bookmark', [\App\Http\Controllers\Feed\BookmarkController::class, 'store'])
        ->middleware(['auth:sanctum', 'require.actor:bookmark', 'throttle:feed-bookmarks']);
    Route::delete('/posts/{post}/bookmark', [\App\Http\Controllers\Feed\BookmarkController::class, 'destroy'])
        ->middleware(['auth:sanctum', 'require.actor:bookmark', 'throttle:feed-bookmarks']);

    // Follows + audiences
    Route::post('/follow/{type}/{id}', [FollowController::class, 'store'])
        ->whereIn('type', ['talent', 'brand'])->whereNumber('id')
        ->middleware(['auth:sanctum', 'require.actor:follow', 'throttle:feed-follows']);
    Route::delete('/follow/{type}/{id}', [FollowController::class, 'destroy'])
        ->whereIn('type', ['talent', 'brand'])->whereNumber('id')
        ->middleware(['auth:sanctum', 'require.actor:follow', 'throttle:feed-follows']);
    Route::get('/talents/{username}/followers', [FollowListController::class, 'followers']);
    Route::get('/talents/{username}/following', [FollowListController::class, 'following']);

    // Composer helpers
    Route::get('/mentions/search', MentionSearchController::class)
        ->middleware('throttle:feed-mentions');
    Route::post('/links/preview', LinkPreviewController::class)
        ->middleware(['auth:sanctum', 'require.actor:link', 'throttle:feed-links']);

    // Reports
    Route::post('/posts/{post}/report', [ReportController::class, 'post'])
        ->middleware(['auth:sanctum', 'require.actor:report']);
    Route::post('/comments/{comment}/report', [ReportController::class, 'comment'])
        ->middleware(['auth:sanctum', 'require.actor:report']);

    // Notifications
    Route::middleware(['auth:sanctum', 'require.actor:notifications'])->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/read', [NotificationController::class, 'markRead']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    });
});
