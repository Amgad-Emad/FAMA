<?php

namespace App\Http\Resources\Feed;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * One notification row (feed-spec §9), localized at READ time in the
 * viewer's locale — payloads store a message key + params, never rendered
 * text.
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DatabaseNotification $notification */
        $notification = $this->resource;

        return [
            'id' => $notification->id,
            'type' => class_basename($notification->type),
            'message' => __($notification->data['message'] ?? '', $notification->data['params'] ?? []),
            'url' => $notification->data['url'] ?? null,
            'actor' => $notification->data['actor'] ?? null,
            'actors_count' => $notification->data['actors_count'] ?? 1,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
