<?php

namespace App\Http\Resources;

use App\Models\Talent;
use Illuminate\Http\Request;

/**
 * @mixin Talent
 */
class TalentCardResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $primary = $this->talentTypes->firstWhere(fn ($type) => (bool) $type->pivot->is_primary)
            ?? $this->talentTypes->first();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'display_name' => $this->display_name,
            'headline' => $this->headline,
            'avatar_url' => $this->avatar_url,
            'city' => $this->base_city,
            'country' => $this->base_country,
            'view_count' => (int) $this->view_count,
            'primary_type' => $primary ? [
                'slug' => $primary->slug,
                'name' => $primary->getTranslation('name', app()->getLocale()),
                'category' => $primary->category,
            ] : null,
        ];
    }
}
