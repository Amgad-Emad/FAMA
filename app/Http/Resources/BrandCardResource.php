<?php

namespace App\Http\Resources;

use App\Models\Brand;
use Illuminate\Http\Request;

/**
 * @mixin Brand
 *
 * Compact brand card for the public, talent-facing brand discovery grid
 * (GET /brands). Identity + a one-line tagline + hiring signal; the logo
 * resolves from the media library (eager-loaded to avoid N+1).
 */
class BrandCardResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_url' => $this->logo_url,
            'industry' => $this->industry ? __(ucfirst(str_replace('_', ' ', $this->industry))) : null,
            'location' => collect([$this->base_city, $this->base_country])->filter()->implode(', ') ?: null,
            'tagline' => $this->getTranslation('description', app()->getLocale(), false) ?: null,
            'is_verified' => (bool) $this->is_verified,
            'campaigns_count' => $this->whenCounted('campaigns'),
        ];
    }
}
