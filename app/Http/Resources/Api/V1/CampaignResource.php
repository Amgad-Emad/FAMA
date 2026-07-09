<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\BaseResource;
use App\Models\Campaign;
use Illuminate\Http\Request;

/**
 * @mixin Campaign
 *
 * The mobile API's PUBLIC campaign contract. Translatable fields (`description`,
 * gallery `caption`) are resolved to the request locale (Accept-Language), unlike
 * the web CampaignResource which returns per-locale maps for the brand editor.
 * Used for public brand profiles and public campaign detail only.
 */
class CampaignResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'description' => $this->getTranslation('description', $locale),
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
                'name' => $type->getTranslation('name', $locale),
                'slug' => $type->slug,
                'quantity' => (int) $type->pivot->quantity,
            ])->values()),
            'gallery' => $this->whenLoaded('gallery', fn () => $this->gallery->map(fn ($item) => [
                'id' => $item->id,
                'media_type' => $item->media_type,
                'media_url' => $item->media_url,
                'thumbnail_url' => $item->thumbnail_url,
                'caption' => $item->getTranslation('caption', $locale),
                'position' => (int) $item->position,
            ])->values()),
        ];
    }
}
