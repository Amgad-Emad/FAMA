<?php

namespace App\Http\Resources;

use App\Models\CampaignMedia;
use Illuminate\Http\Request;

/**
 * @mixin CampaignMedia
 */
class CampaignMediaResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'media_type' => $this->media_type,
            'media_url' => $this->media_url,
            'thumbnail_url' => $this->thumbnail_url,
            'caption' => $this->getTranslations('caption'),
            'position' => (int) $this->position,
        ];
    }
}
