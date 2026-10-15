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
            // A readable name for the subject when it still exists (talent, brand, flow…).
            'subject_name' => $this->subjectName(),
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

    /** The subject's display name, when it still exists (translatable names resolve to the current locale). */
    private function subjectName(): ?string
    {
        if (! $this->subject) {
            return null;
        }
        foreach (['display_name', 'name', 'title', 'reviewer_name'] as $attr) {
            $value = $this->subject->getAttribute($attr);
            if (is_array($value)) {
                $value = $value[app()->getLocale()] ?? $value['en'] ?? reset($value);
            }
            if (filled($value) && is_string($value)) {
                return $value;
            }
        }

        return null;
    }
}
