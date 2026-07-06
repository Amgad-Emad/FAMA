<?php

namespace App\Http\Resources;

use App\Models\PressFeature;
use Illuminate\Http\Request;

/**
 * @mixin PressFeature
 */
class PressResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'publication' => $this->publication,
            'title' => $this->title,
            'url' => $this->url,
            'published_date' => $this->published_date?->toDateString(),
            'position' => (int) $this->position,
        ];
    }
}
