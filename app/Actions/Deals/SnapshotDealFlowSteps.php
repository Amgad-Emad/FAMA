<?php

namespace App\Actions\Deals;

use App\Actions\Contracts\Action;
use App\Models\Deal;
use App\Models\DealFlow;

/**
 * Copy a flow's steps into a deal's `deal_steps` at creation (ADR-4). The
 * snapshot includes settings, so later template edits never change an in-flight
 * deal. All steps start `pending`.
 */
class SnapshotDealFlowSteps implements Action
{
    public function __invoke(Deal $deal, DealFlow $flow): void
    {
        foreach ($flow->steps as $flowStep) {
            $deal->steps()->create([
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
