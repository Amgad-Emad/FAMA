<?php

namespace App\Http\Resources;

use App\Models\TalentType;
use Illuminate\Http\Request;

/**
 * @mixin TalentType
 */
class TalentTypeResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->getTranslations('name'),
            'category' => $this->category,
            'icon' => $this->icon,
            'is_primary' => (bool) ($this->pivot?->is_primary ?? false),
            'position' => (int) ($this->pivot?->position ?? 0),
        ];
    }
}
