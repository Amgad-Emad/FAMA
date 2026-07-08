<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * @mixin Activity
 */
class ActivityResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'description' => $this->description,
            'subject_type' => class_basename($this->subject_type ?? ''),
            'subject_id' => $this->subject_id,
            'causer' => $this->causer_id !== null ? [
                'type' => class_basename($this->causer_type ?? ''),
                'id' => $this->causer_id,
                'name' => $this->causer?->name ?? $this->causer?->getAttribute('display_name'),
            ] : null,
            'changes' => $this->attribute_changes,
            'properties' => $this->properties,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
