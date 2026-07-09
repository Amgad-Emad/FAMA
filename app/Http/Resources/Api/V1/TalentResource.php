<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\BaseResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\ServiceResource;
use App\Models\Talent;
use Illuminate\Http\Request;

/**
 * @mixin Talent
 *
 * The mobile API's talent contract — the full public passport. Translatable
 * fields (headline/bio, profession names) are returned as single strings in the
 * request locale (negotiated by SetApiLocale), not per-locale maps. Nested
 * collections are only included when eager-loaded, keeping list vs. detail
 * payloads explicit.
 */
class TalentResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'display_name' => $this->display_name,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_url,
            'hero_image_url' => $this->hero_image_url,
            'base_city' => $this->base_city,
            'base_country' => $this->base_country,
            'availability_status' => (string) $this->availability_status,
            'rate_tier' => $this->rate_tier,
            'booking_type' => $this->booking_type,
            'booking_value' => $this->booking_value !== null ? (float) $this->booking_value : null,
            'willing_to_travel' => (bool) $this->willing_to_travel,
            'is_published' => (bool) $this->is_published,
            'view_count' => (int) $this->view_count,
            'talent_types' => $this->whenLoaded('talentTypes', fn () => $this->talentTypes->map(fn ($type) => [
                'slug' => $type->slug,
                'name' => $type->getTranslation('name', $locale),
                'category' => $type->category,
                'is_primary' => (bool) $type->pivot->is_primary,
            ])->values()),
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
