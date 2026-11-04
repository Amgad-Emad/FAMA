<?php

namespace App\Http\Controllers\Feed;

use App\Http\Resources\Feed\NotificationResource;
use App\Services\NotificationInboxService;
use Illuminate\Contracts\View\View;
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
     * The inbox, newest first. ?filter=unread narrows to unread rows and
     * ?filter=mention to post/comment mentions (the fama-ui page's
     * All / Unread / Mentions pills, whose counts ride in meta.counts).
     * Follow rows carry whether the viewer already follows them back.
     */
    public function index(Request $request, NotificationInboxService $inbox): JsonResponse
    {
        $actor = $this->currentActor->resolve();
        $page = $inbox->page($actor, $request->string('filter')->value() ?: null);
        $followedBack = $inbox->followedBack($actor, $page->getCollection());

        $rows = $page->getCollection()->map(function (DatabaseNotification $notification) use ($followedBack) {
            $actorKey = ($notification->data['actor']['type'] ?? '').':'.($notification->data['actor']['id'] ?? '');

            return new NotificationResource($notification, $followedBack[$actorKey] ?? null);
        })->values();

        return response()->paginated($page, $rows, null, ['counts' => $inbox->counts($actor)]);
    }

    /**
     * The full notifications page (fama-ui) — a shell; the list (and the pill
     * counts) load over Ajax from index().
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
