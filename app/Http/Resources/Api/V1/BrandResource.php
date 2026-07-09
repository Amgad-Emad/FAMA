<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\BaseResource;
use App\Http\Resources\BrandReviewResource;
use App\Models\Brand;
use Illuminate\Http\Request;

/**
 * @mixin Brand
 *
 * The mobile API's brand contract. `description` is translatable and returned as
 * a single string in the request locale (negotiated by SetApiLocale) — the web
 * BrandResource returns the full per-locale map for the editor, whereas mobile
 * consumers want the resolved language only. Satellite data (credibility,
 * aesthetic, social, images, approved reviews, public campaigns) is included only
 * when eager-loaded, so the public profile and the discovery card stay distinct.
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
            'credibility' => $this->whenLoaded('credibility', fn () => $this->credibility
                ? new CredibilityResource($this->credibility)
                : null),
            'aesthetic' => $this->whenLoaded('aesthetic', fn () => $this->aesthetic ? [
                'brand_references' => $this->aesthetic->brand_references,
                'mood_tags' => $this->aesthetic->moodTags->pluck('tag')->values(),
            ] : null),
            'social_handles' => $this->whenLoaded('socialHandles', fn () => $this->socialHandles->map(fn ($h) => [
                'id' => $h->id,
                'platform' => $h->platform,
                'handle' => $h->handle,
                'url' => $h->url,
            ])->values()),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($i) => [
                'id' => $i->id,
                'image_url' => $i->image_url,
                'thumbnail_url' => $i->thumbnail_url,
            ])->values()),
            'reviews' => BrandReviewResource::collection($this->whenLoaded('brandReviews')),
            'campaigns' => CampaignResource::collection($this->whenLoaded('campaigns')),
        ];
    }
}
