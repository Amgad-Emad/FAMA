<?php

namespace App\Listeners;

use App\Events\DealCompleted;
use App\Services\BrandCredibilityService;

/**
 * On deal completion, recompute the brand's credibility counters (auto-discovered
 * by the DealCompleted type-hint). Monotonic + automatic — the brand takes no
 * action.
 */
class AccrueBrandCredibility
{
    public function __construct(private readonly BrandCredibilityService $credibility) {}

    public function handle(DealCompleted $event): void
    {
        $brand = $event->deal->loadMissing('brand')->brand;

        if ($brand !== null) {
            $this->credibility->recalculate($brand);
        }
    }
}
