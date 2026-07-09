<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\BaseResource;
use App\Models\TalentType;
use Illuminate\Http\Request;

/**
 * @mixin TalentType
 *
 * A talent-type catalog entry for the mobile lookups — `name` / `description`
 * resolved to the request locale (Accept-Language), unlike the web
 * TalentTypeResource which returns per-locale maps.
 */
class TalentTypeResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->getTranslation('name', $locale),
            'category' => $this->category,
            'icon' => $this->icon,
            'description' => $this->description ? $this->getTranslation('description', $locale) : null,
        ];
    }
}
