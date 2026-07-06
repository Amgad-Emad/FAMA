<?php

namespace App\Actions\Deals;

use App\Actions\Contracts\Action;
use App\Deals\DealProgression;
use App\Models\Deal;
use App\Models\DealFlow;

/**
 * Create a deal, snapshot its flow into deal_steps, and activate the first
 * actionable step (auto-completing any leading system steps) — which flips the
 * status to that step's actor. Runs inside DealService's transaction.
 */
class InitiateDeal implements Action
{
    public function __construct(
        private readonly SnapshotDealFlowSteps $snapshot,
        private readonly DealProgression $progression,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  brand_id, talent_id, service_id?, title, brief?, currency?, initiated_by, start_date?, end_date?
     */
    public function __invoke(array $attributes, DealFlow $flow): Deal
    {
        $deal = Deal::create($attributes + [
            'deal_flow_id' => $flow->id,
            'reference' => $this->nextReference(),
            'currency' => $attributes['currency'] ?? 'EGP',
            'status' => 'draft',
        ]);

        ($this->snapshot)($deal, $flow);

        $this->progression->activateNext($deal);

        return $deal->refresh();
    }

    /**
     * Next human deal reference for the current year (FAMA-YYYY-NNNN).
     */
    private function nextReference(): string
    {
        $year = (int) now()->year;
        $sequence = Deal::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return Deal::makeReference($year, $sequence);
    }
}
