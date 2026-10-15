<?php

namespace App\Http\Controllers\Feed;

use App\Http\Resources\Feed\NotificationResource;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The actor's notification inbox (feed-spec §9): paginated 20, localized at
 * READ time in the viewer's locale (payloads store an English message key +
 * params, never pre-rendered text), mark-read (all or by IDs), unread badge.
 */
class NotificationController extends FeedBaseController
{
    /**
     * The inbox, newest first. ?filter=unread narrows to unread rows and
     * ?filter=mention to post/comment mentions (the fama-ui page's
     * All / Unread / Mentions segments).
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $this->currentActor->resolve();

        $page = $actor->notifications()
            ->when($request->query('filter') === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($request->query('filter') === 'mention', fn ($query) => $query->where('type', 'like', '%Mentioned%'))
            ->paginate(20)
            ->withQueryString();

        return response()->paginated(
            $page,
            NotificationResource::collection($page->getCollection()),
        );
    }

    /**
     * The full notifications page (fama-ui) — a shell; the list loads over Ajax
     * from index().
     */
    public function page(): View
    {
        return view('feed.notifications');
    }

    /**
     * Mark all — or the given IDs — read.
     */
    public function markRead(Request $request): JsonResponse
    {
        $actor = $this->currentActor->resolve();
        $ids = $request->input('ids');

        $query = $actor->unreadNotifications();

        if (is_array($ids) && $ids !== []) {
            $query->whereIn('id', $ids);
        }

        $query->update(['read_at' => now()]);

        return response()->success(message: __('Notifications marked read.'));
    }

    /**
     * The unread badge count.
     */
    public function unreadCount(): JsonResponse
    {
        return response()->success([
            'count' => $this->currentActor->resolve()->unreadNotifications()->count(),
        ]);
    }
}
