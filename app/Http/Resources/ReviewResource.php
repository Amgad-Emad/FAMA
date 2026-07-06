<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;

/**
 * @mixin Review
 */
class ReviewResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reviewer_name' => $this->reviewer_name,
            'reviewer_role' => $this->reviewer_role,
            'reviewer_company' => $this->reviewer_company,
            'rating' => (int) $this->rating,
            'body' => $this->body,
            'project_type' => $this->project_type,
            'status' => (string) $this->status,
            'is_approved' => (bool) $this->is_approved,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
