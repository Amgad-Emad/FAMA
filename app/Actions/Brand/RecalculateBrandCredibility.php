<?php

namespace App\Actions\Brand;

use App\Actions\Contracts\Action;
use App\Models\Brand;
use App\Models\BrandCredibility;
use App\Models\DealStep;

/**
 * Recompute a brand's denormalized credibility counters from its deals
 * (brand-spec workflow 8). `completed_projects_count` is monotonic (completed
 * deals only accrue); response metrics recalculate; `brief_quality_score` is
 * internal. Idempotent — safe to run on every deal completion.
 */
class RecalculateBrandCredibility implements Action
{
    public function __invoke(Brand $brand): BrandCredibility
    {
        $deals = $brand->deals()->get(['id', 'status', 'created_at']);
        $total = $deals->count();
        $completed = $deals->filter(fn ($deal) => $deal->status->getValue() === 'completed')->count();
        $engaged = $deals->filter(fn ($deal) => ! in_array($deal->status->getValue(), ['draft', 'declined', 'expired'], true))->count();

        $responseRate = $total > 0 ? (int) round($engaged / $total * 100) : null;

        // Average hours from a deal's creation to the brand's first completed step.
        $brandSteps = DealStep::query()
            ->whereIn('deal_id', $deals->pluck('id'))
            ->where('actor', 'brand')->where('status', 'completed')->whereNotNull('completed_at')
            ->with('deal:id,created_at')
            ->get();
        $avgHours = $brandSteps->isNotEmpty()
            ? round((float) $brandSteps->avg(fn ($step) => abs($step->deal->created_at->diffInMinutes($step->completed_at)) / 60), 2)
            : null;

        // Internal brief-quality proxy: approved-review "creative respect" sentiment.
        $reviewAvg = $brand->brandReviews()->where('is_approved', true)->avg('creative_respect_rating');
        $briefQuality = $reviewAvg !== null ? round((float) $reviewAvg, 2) : null;

        return $brand->credibility()->updateOrCreate([], [
            'completed_projects_count' => $completed,
            'response_rate_pct' => $responseRate,
            'avg_response_time_hours' => $avgHours,
            'brief_quality_score' => $briefQuality,
        ]);
    }
}
