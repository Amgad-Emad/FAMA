<?php

namespace App\Http\Controllers\Feed;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * The actor's notification inbox (feed-spec §9): paginated 20, localized at
 * READ time in the viewer's locale (payloads store an English message key +
 * params, never pre-rendered text), mark-read (all or by IDs), unread badge.
 */
class NotificationController extends FeedBaseController
{
    /**
     * The inbox, newest first.
     */
    public function index(): JsonResponse
    {
        $actor = $this->currentActor->resolve();

        $page = $actor->notifications()->paginate(20);

        return response()->paginated(
            $page,
            \App\Http\Resources\Feed\NotificationResource::collection($page->getCollection()),
        );
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
