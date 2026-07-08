<?php

namespace App\Http\Resources;

use App\Models\BrandReview;
use Illuminate\Http\Request;

/**
 * @mixin BrandReview
 */
class BrandReviewResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'communication_rating' => (int) $this->communication_rating,
            'fairness_rating' => (int) $this->fairness_rating,
            'creative_respect_rating' => (int) $this->creative_respect_rating,
            'average_rating' => $this->average_rating,
            'body' => $this->body,
            'status' => $this->status->getValue(),
            'is_approved' => (bool) $this->is_approved,
            'talent' => $this->whenLoaded('talent', fn () => [
                'display_name' => $this->talent?->display_name,
                'slug' => $this->talent?->slug,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
