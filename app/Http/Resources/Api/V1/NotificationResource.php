<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @mixin DatabaseNotification
 *
 * A stored notification as the mobile app reads it. `type` is the short type from
 * the payload (e.g. `deal.turn`, `deal.message`); `data` is the full contract the
 * notification class wrote (App\Notifications\*).
 */
class NotificationResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => data_get($this->data, 'type'),
            'data' => $this->data,
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
