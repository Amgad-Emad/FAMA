<?php

namespace App\Http\Resources;

use App\Models\BrandProject;
use Illuminate\Http\Request;

/**
 * @mixin BrandProject
 *
 * Compact project card for the public, talent-facing opportunities grid
 * (GET /projects) — an open, public project the talent can browse and message
 * the brand about. Brand + roles + cover are eager-loaded to avoid N+1.
 */
class PublicProjectCardResource extends BaseResource
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
            'cover_image_url' => $this->cover_image_url,
            'status' => $this->status->getValue(),
            // Budget is only exposed publicly when the brand opts in (private by default).
            'budget_min' => $this->budget_is_public && $this->budget_min !== null ? (float) $this->budget_min : null,
            'budget_max' => $this->budget_is_public && $this->budget_max !== null ? (float) $this->budget_max : null,
            'currency' => $this->currency,
            'location' => collect([$this->location_city, $this->location_country])->filter()->implode(', ') ?: null,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'logo_url' => $this->brand->logo_url,
            ] : null),
            // The single role/discipline sought (one role, one position).
            'role' => $this->whenLoaded('talentType', fn () => $this->talentType ? [
                'name' => $this->talentType->getTranslation('name', app()->getLocale()),
                'slug' => $this->talentType->slug,
            ] : null),
        ];
    }
}
