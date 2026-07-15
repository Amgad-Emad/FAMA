<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use Illuminate\Http\Request;

/**
 * @mixin Campaign
 *
 * Compact campaign card for the public, talent-facing opportunities grid
 * (GET /campaigns) — an open, public campaign the talent can browse and message
 * the brand about. Brand + roles + cover are eager-loaded to avoid N+1.
 */
class PublicCampaignCardResource extends BaseResource
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
            'budget_min' => $this->budget_min !== null ? (float) $this->budget_min : null,
            'budget_max' => $this->budget_max !== null ? (float) $this->budget_max : null,
            'currency' => $this->currency,
            'location' => collect([$this->location_city, $this->location_country])->filter()->implode(', ') ?: null,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'logo_url' => $this->brand->logo_url,
            ] : null),
            'roles' => $this->whenLoaded('talentTypes', fn () => $this->talentTypes->map(fn ($type) => [
                'name' => $type->getTranslation('name', app()->getLocale()),
                'quantity' => (int) $type->pivot->quantity,
            ])->values()),
        ];
    }
}
