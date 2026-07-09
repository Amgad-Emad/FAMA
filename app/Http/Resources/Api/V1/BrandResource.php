<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\BaseResource;
use App\Models\Brand;
use Illuminate\Http\Request;

/**
 * @mixin Brand
 *
 * The mobile API's brand contract. `description` is translatable and returned as
 * a single string in the request locale (negotiated by SetApiLocale) — the web
 * BrandResource returns the full per-locale map for the editor, whereas mobile
 * consumers want the resolved language only.
 */
class BrandResource extends BaseResource
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
            'description' => $this->description,
            'industry' => $this->industry,
            'brand_stage' => $this->brand_stage,
            'base_city' => $this->base_city,
            'base_country' => $this->base_country,
            'geographic_reach' => $this->geographic_reach,
            'founded_year' => $this->founded_year,
            'company_size' => $this->company_size,
            'website' => $this->website,
            'logo_url' => $this->logo_url,
            'cover_image_url' => $this->cover_image_url,
            'is_complete' => (bool) $this->is_complete,
            'is_published' => (bool) $this->is_published,
            'is_verified' => (bool) $this->is_verified,
            'status' => $this->status->getValue(),
        ];
    }
}
