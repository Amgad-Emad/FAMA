<?php

namespace App\Listeners;

use App\Events\ContractCompleted;
use App\Services\BrandCredibilityService;

/**
 * On contract completion, recompute the brand's credibility counters (auto-discovered
 * by the ContractCompleted type-hint). Monotonic + automatic — the brand takes no
 * action.
 */
class AccrueBrandCredibility
{
    public function __construct(private readonly BrandCredibilityService $credibility) {}

    public function handle(ContractCompleted $event): void
    {
        $brand = $event->contract->loadMissing('brand')->brand;

        if ($brand !== null) {
            $this->credibility->recalculate($brand);
        }
    }
}
