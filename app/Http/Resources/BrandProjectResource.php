<?php

namespace App\Http\Resources;

use App\Models\BrandProject;
use Illuminate\Http\Request;

/**
 * @mixin BrandProject
 */
class BrandProjectResource extends BaseResource
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
            'budget_is_public' => (bool) $this->budget_is_public,
            'talent_type_id' => $this->talent_type_id,
            'cover_image_url' => $this->cover_image_url,
            // The single role/discipline this project seeks (one role, one position).
            'role' => $this->whenLoaded('talentType', fn () => $this->talentType ? [
                'talent_type_id' => $this->talentType->id,
                'name' => $this->talentType->getTranslation('name', app()->getLocale()),
                'slug' => $this->talentType->slug,
            ] : null),
            'gallery' => $this->whenLoaded('gallery', fn () => BrandProjectMediaResource::collection($this->gallery)),
            'contracts_count' => $this->whenCounted('contracts'),
        ];
    }
}
