<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Notifications
 *
 * @authenticated
 *
 * The authenticated entity's notification feed — deal turn changes and new deal
 * messages (App\Notifications\*), stored on the polymorphic `notifications` table
 * and readable by a talent or brand token. Delivery is intentionally basic
 * (database channel, written synchronously as deals progress); the contract here
 * is stable so push/email channels can be layered on later without changing it.
 */
class NotificationController extends Controller
{
    /**
     * List my notifications
     *
     * Paginated, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $request->user()->notifications()->paginate(20);

        return response()->paginated($paginator, NotificationResource::collection($paginator->getCollection()));
    }

    /**
     * Unread count
     *
     * The number of unread notifications (for a badge).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->success(['unread' => $request->user()->unreadNotifications()->count()]);
    }

    /**
     * Mark one read
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->success(null, __('Marked as read.'));
    }

    /**
     * Mark all read
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->success(['unread' => 0], __('All notifications marked as read.'));
    }
}
