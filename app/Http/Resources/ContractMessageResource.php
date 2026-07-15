<?php

namespace App\Http\Resources;

use App\Models\ContractMessage;
use Illuminate\Http\Request;

/**
 * @mixin ContractMessage
 */
class ContractMessageResource extends BaseResource
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
            'is_rich' => (bool) $this->is_rich,
            'is_system' => $this->isSystem(),
            // Uploaded application/message files ([{name, url, size}]).
            'attachments' => $this->file_attachments,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
