<?php

use App\Http\Controllers\Feed\CommentController;
use App\Http\Controllers\Feed\FeedController;
use App\Http\Controllers\Feed\FollowController;
use App\Http\Controllers\Feed\LikeController;
use App\Http\Controllers\Feed\PostController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Feed routes (Phase 5 — feed-spec §12, ADR-F8)
|--------------------------------------------------------------------------
|
| Required from routes/web.php INSIDE the locale group, before the /{slug}
| talent catch-all. Every route resolves the actor (resolve.actor — never
| rejects); write routes additionally require one (require.actor:<action> —
| guests get the 401-with-intent envelope and post-auth replay). Reads are
| fully public. The full HTTP surface (composer, casting strip, reports,
| notifications, /api/v1 mirror) lands with Prompt 5K.
|
*/

Route::middleware('resolve.actor')->group(function () {

    // --- Design-system preview (Prompt 5L review surface) --------------------
    Route::view('/design/feed-tokens', 'design.feed-tokens')->name('design.feed-tokens');

    // --- Pages + the ranked stream -------------------------------------------
    Route::get('/feed', [FeedController::class, 'index'])->name('feed');
    Route::get('/feed/items', [FeedController::class, 'items'])->name('feed.items');
    Route::get('/feed/casting', [FeedController::class, 'casting'])->name('feed.casting');
    Route::get('/posts/{post}', [PostController::class, 'show'])->whereNumber('post')->name('posts.show');
    Route::get('/posts/{post}/fragment', [PostController::class, 'fragment'])->name('posts.fragment');
    Route::get('/talents/{username}/posts', [PostController::class, 'byTalent'])->name('talents.posts');

    // --- Post write path (authors; PostPolicy gates talents-only) ------------
    Route::post('/posts', [PostController::class, 'store'])
        ->middleware(['require.actor:post', 'throttle:feed-posts'])->name('posts.store');
    Route::patch('/posts/{post}', [PostController::class, 'update'])
        ->middleware('require.actor:post')->name('posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])
        ->middleware('require.actor:post')->name('posts.destroy');
    Route::post('/posts/{post}/pin', [PostController::class, 'pin'])
        ->middleware('require.actor:post')->name('posts.pin');
    Route::post('/projects/{project}/share', \App\Http\Controllers\Feed\ProjectShareController::class)
        ->middleware('require.actor:share')->name('projects.share');

    // --- Comments (read is public; write requires an actor) ------------------
    Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('posts.comments');
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->middleware(['require.actor:comment', 'throttle:feed-comments'])->name('posts.comments.store');
    Route::get('/comments/{comment}/replies', [CommentController::class, 'replies'])->name('comments.replies');
    Route::patch('/comments/{comment}', [CommentController::class, 'update'])
        ->middleware('require.actor:comment')->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->middleware('require.actor:comment')->name('comments.destroy');

    // --- Likes ---------------------------------------------------------------
    Route::post('/posts/{post}/like', [LikeController::class, 'store'])
        ->middleware(['require.actor:like', 'throttle:feed-likes'])->name('posts.like');
    Route::delete('/posts/{post}/like', [LikeController::class, 'destroy'])
        ->middleware(['require.actor:like', 'throttle:feed-likes'])->name('posts.unlike');
    Route::post('/comments/{comment}/like', [LikeController::class, 'storeComment'])
        ->middleware(['require.actor:like', 'throttle:feed-likes'])->name('comments.like');
    Route::delete('/comments/{comment}/like', [LikeController::class, 'destroyComment'])
        ->middleware(['require.actor:like', 'throttle:feed-likes'])->name('comments.unlike');

    // --- Follow-suggestion rails (5Z) ----------------------------------------
    Route::get('/feed/suggestions/talents', [\App\Http\Controllers\Feed\SuggestionController::class, 'talents'])
        ->name('feed.suggestions.talents');
    Route::get('/feed/suggestions/brands', [\App\Http\Controllers\Feed\SuggestionController::class, 'brands'])
        ->middleware('feature:brands')->name('feed.suggestions.brands');
    Route::post('/feed/suggestions/dismiss', [\App\Http\Controllers\Feed\SuggestionController::class, 'dismiss'])
        ->middleware(['require.actor:dismiss', 'throttle:feed-dismissals'])->name('feed.suggestions.dismiss');

    // --- Saves (5X) ----------------------------------------------------------
    Route::post('/posts/{post}/bookmark', [\App\Http\Controllers\Feed\BookmarkController::class, 'store'])
        ->middleware(['require.actor:bookmark', 'throttle:feed-bookmarks'])->name('posts.bookmark');
    Route::delete('/posts/{post}/bookmark', [\App\Http\Controllers\Feed\BookmarkController::class, 'destroy'])
        ->middleware(['require.actor:bookmark', 'throttle:feed-bookmarks'])->name('posts.unbookmark');

    // --- Mention autocomplete (composer) -------------------------------------
    Route::get('/mentions/search', \App\Http\Controllers\Feed\MentionSearchController::class)
        ->middleware('throttle:feed-mentions')->name('mentions.search');

    // --- Link chip (composer on-paste) ---------------------------------------
    Route::post('/links/preview', \App\Http\Controllers\Feed\LinkPreviewController::class)
        ->middleware(['require.actor:link', 'throttle:feed-links'])->name('links.preview');

    // --- Reports -------------------------------------------------------------
    Route::post('/posts/{post}/report', [\App\Http\Controllers\Feed\ReportController::class, 'post'])
        ->middleware('require.actor:report')->name('posts.report');
    Route::post('/comments/{comment}/report', [\App\Http\Controllers\Feed\ReportController::class, 'comment'])
        ->middleware('require.actor:report')->name('comments.report');

    // --- Notifications (actor-only reads) ------------------------------------
    Route::middleware('require.actor:notifications')->group(function () {
        Route::get('/notifications', [\App\Http\Controllers\Feed\NotificationController::class, 'index'])
            ->name('notifications');
        Route::post('/notifications/read', [\App\Http\Controllers\Feed\NotificationController::class, 'markRead'])
            ->name('notifications.read');
        // fama-ui notifications page (the bell's "See all notifications").
        Route::get('/notifications/all', [\App\Http\Controllers\Feed\NotificationController::class, 'page'])
            ->name('notifications.page');
        Route::get('/notifications/unread-count', [\App\Http\Controllers\Feed\NotificationController::class, 'unreadCount'])
            ->name('notifications.unread');
    });

    // --- Audience listings (public reads) ------------------------------------
    Route::get('/talents/{username}/followers', [\App\Http\Controllers\Feed\FollowListController::class, 'followers'])
        ->name('talents.followers');
    Route::get('/talents/{username}/following', [\App\Http\Controllers\Feed\FollowListController::class, 'following'])
        ->name('talents.following');

    // --- Follows -------------------------------------------------------------
    Route::post('/follow/{type}/{id}', [FollowController::class, 'store'])
        ->whereIn('type', ['talent', 'brand'])->whereNumber('id')
        ->middleware(['require.actor:follow', 'throttle:feed-follows'])->name('follow');
    Route::delete('/follow/{type}/{id}', [FollowController::class, 'destroy'])
        ->whereIn('type', ['talent', 'brand'])->whereNumber('id')
        ->middleware(['require.actor:follow', 'throttle:feed-follows'])->name('unfollow');
});
