<?php

namespace App\Http\Resources;

use App\Models\BlockType;
use Illuminate\Http\Request;

/**
 * @mixin BlockType
 */
class BlockTypeResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->getTranslations('name'),
            'description' => $this->getTranslations('description'),
            'icon' => $this->icon,
            'availability' => $this->availability,
            'content_source' => $this->content_source,
            'default_layout' => $this->default_layout,
            'is_repeatable' => (bool) $this->is_repeatable,
        ];
    }
}
