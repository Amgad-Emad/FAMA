<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\BaseResource;
use App\Models\BrandCredibility;
use Illuminate\Http\Request;

/**
 * @mixin BrandCredibility
 *
 * A brand's accrued credibility — the read-only trust signals that grow as deals
 * complete (monotonic; recalculated by the DealCompleted listener).
 */
class CredibilityResource extends BaseResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'completed_projects_count' => (int) $this->completed_projects_count,
            'avg_response_time_hours' => $this->avg_response_time_hours !== null ? (float) $this->avg_response_time_hours : null,
            'response_rate_pct' => (int) $this->response_rate_pct,
            'brief_quality_score' => $this->brief_quality_score !== null ? (float) $this->brief_quality_score : null,
        ];
    }
}
