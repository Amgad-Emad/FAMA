<?php

namespace App\Actions\Contracting;

use App\Actions\Contracts\Action;
use App\Models\Contract;
use App\Models\ContractFlow;

/**
 * Copy a flow's steps into a contract's `contract_steps` at creation (ADR-4). The
 * snapshot includes settings, so later template edits never change an in-flight
 * contract. All steps start `pending`.
 */
class SnapshotContractFlowSteps implements Action
{
    public function __invoke(Contract $contract, ContractFlow $flow): void
    {
        foreach ($flow->steps as $flowStep) {
            $contract->steps()->create([
                'flow_step_id' => $flowStep->id,
                'key' => $flowStep->key,
                'name' => $flowStep->name,
                'actor' => $flowStep->actor,
                'step_type' => $flowStep->step_type,
                'position' => $flowStep->position,
                'status' => 'pending',
                'is_required' => $flowStep->is_required,
                'is_skippable' => $flowStep->is_skippable,
                'settings' => array_merge($flowStep->settings ?? [], ['instructions' => $flowStep->instructions]),
            ]);
        }
    }
}
