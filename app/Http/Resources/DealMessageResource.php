<?php

namespace App\Http\Resources;

use App\Models\DealMessage;
use Illuminate\Http\Request;

/**
 * @mixin DealMessage
 */
class DealMessageResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'sender_role' => $this->sender_role,
            'body' => $this->body,
            'is_system' => $this->isSystem(),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
