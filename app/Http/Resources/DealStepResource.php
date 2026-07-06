<?php

namespace App\Http\Resources;

use App\Models\DealStep;
use Illuminate\Http\Request;

/**
 * @mixin DealStep
 */
class DealStepResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'actor' => $this->actor,
            'step_type' => $this->step_type,
            'position' => (int) $this->position,
            'status' => (string) $this->status,
            'is_current' => $this->status->isCurrent(),
            'is_required' => (bool) $this->is_required,
            'is_skippable' => (bool) $this->is_skippable,
            'instructions' => data_get($this->settings, 'instructions'),
            'fields' => data_get($this->settings, 'fields', []),
            'payload' => $this->payload,
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
