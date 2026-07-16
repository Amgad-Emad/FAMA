<?php

namespace App\Http\Resources;

use App\Models\ProfileBlock;
use Illuminate\Http\Request;

/**
 * @mixin ProfileBlock
 */
class ProfileBlockResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'talent_type_id' => $this->talent_type_id,
            'position' => (int) $this->position,
            'is_visible' => (bool) $this->is_visible,
            'status' => (string) $this->status,
            'layout' => $this->layout,
            'title' => $this->getTranslations('title'),
            'settings' => $this->settings,
            'content' => $this->content,
            'block_type' => [
                'id' => $this->blockType->id,
                'key' => $this->blockType->key,
                'name' => $this->blockType->getTranslations('name'),
                'icon' => $this->blockType->icon,
                'content_source' => $this->blockType->content_source,
                'default_layout' => $this->blockType->default_layout,
            ],
        ];
    }
}
