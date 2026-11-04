<?php

namespace App\Http\Resources\Feed;

use App\Services\NotificationInboxService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * One notification row (feed-spec §9), localized at READ time in the
 * viewer's locale — payloads store a message key + params, never rendered
 * text. Carries the `kind` the notifications page picks each card's one
 * action from, the one-line `excerpt` (comments, replies, mentions; null on
 * rows stored before it existed) and, for a new-follower row, whether the
 * viewer already follows them back.
 */
class NotificationResource extends JsonResource
{
    /**
     * @param  DatabaseNotification  $resource
     * @param  bool|null  $followedBack  Follow rows only: the viewer already follows the actor.
     */
    public function __construct($resource, private readonly ?bool $followedBack = null)
    {
        parent::__construct($resource);
    }

    /**
     * The row as the bell popover, the notifications page and the API read it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DatabaseNotification $notification */
        $notification = $this->resource;
        $kind = NotificationInboxService::kind($notification->type);

        return [
            'id' => $notification->id,
            'type' => class_basename($notification->type),
            'kind' => $kind,
            'message' => __($notification->data['message'] ?? '', $notification->data['params'] ?? []),
            'excerpt' => $notification->data['excerpt'] ?? null,
            'url' => $notification->data['url'] ?? null,
            'actor' => $notification->data['actor'] ?? null,
            'actors_count' => $notification->data['actors_count'] ?? 1,
            'followed_back' => $kind === 'follow' ? (bool) $this->followedBack : null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
