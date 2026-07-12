<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use Illuminate\Http\Request;

/**
 * @mixin Campaign
 */
class CampaignResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->getTranslations('description'),
            'status' => $this->status->getValue(),
            'budget_min' => $this->budget_min !== null ? (float) $this->budget_min : null,
            'budget_max' => $this->budget_max !== null ? (float) $this->budget_max : null,
            'currency' => $this->currency,
            'location_city' => $this->location_city,
            'location_country' => $this->location_country,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'is_public' => (bool) $this->is_public,
            'positions_count' => (int) $this->positions_count,
            'cover_image_url' => $this->cover_image_url,
            'roles' => $this->whenLoaded('talentTypes', fn () => $this->talentTypes->map(fn ($type) => [
                'talent_type_id' => $type->id,
                'name' => $type->getTranslation('name', app()->getLocale()),
                'slug' => $type->slug,
                'quantity' => (int) $type->pivot->quantity,
            ])->values()),
            'gallery' => $this->whenLoaded('gallery', fn () => CampaignMediaResource::collection($this->gallery)),
            'deals_count' => $this->whenCounted('deals'),
        ];
    }
}
