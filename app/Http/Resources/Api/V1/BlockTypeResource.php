<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\BaseResource;
use App\Models\BlockType;
use Illuminate\Http\Request;

/**
 * @mixin BlockType
 *
 * A block-type catalog entry for the mobile lookups — `name` / `description`
 * resolved to the request locale. Drives which profile blocks the app can offer
 * and how they render (content_source + default_layout).
 */
class BlockTypeResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->getTranslation('name', $locale),
            'description' => $this->description ? $this->getTranslation('description', $locale) : null,
            'icon' => $this->icon,
            'availability' => $this->availability,
            'content_source' => $this->content_source,
            'default_layout' => $this->default_layout,
            'is_repeatable' => (bool) $this->is_repeatable,
        ];
    }
}
