<?php

namespace App\Actions\Contracting;

use App\Actions\Contracts\Action;
use App\Contracting\ContractProgression;
use App\Models\Contract;
use App\Models\ContractFlow;

/**
 * Create a contract, snapshot its flow into contract_steps, and activate the first
 * actionable step (auto-completing any leading system steps) — which flips the
 * status to that step's actor. Runs inside ContractService's transaction.
 */
class InitiateContract implements Action
{
    public function __construct(
        private readonly SnapshotContractFlowSteps $snapshot,
        private readonly ContractProgression $progression,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  brand_id, talent_id, title, brief?, currency?, initiated_by, start_date?, end_date?
     */
    public function __invoke(array $attributes, ContractFlow $flow): Contract
    {
        $contract = Contract::create($attributes + [
            'contract_flow_id' => $flow->id,
            'reference' => $this->nextReference(),
            'currency' => $attributes['currency'] ?? 'EGP',
            'status' => 'draft',
        ]);

        ($this->snapshot)($contract, $flow);

        $this->progression->activateNext($contract);

        return $contract->refresh();
    }

    /**
     * Next human contract reference for the current year (FAMA-YYYY-NNNN).
     */
    private function nextReference(): string
    {
        $year = (int) now()->year;
        $sequence = Contract::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return Contract::makeReference($year, $sequence);
    }
}
