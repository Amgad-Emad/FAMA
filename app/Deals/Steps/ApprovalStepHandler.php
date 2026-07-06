<?php

namespace App\Deals\Steps;

use App\Models\DealStep;

/**
 * approval — the actor approves the previous step's output. Approving advances
 * the deal; rejecting is a separate path (DealService::rejectStep → loop back).
 */
class ApprovalStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'approval';
    }

    public function validate(DealStep $step, array $input): array
    {
        return $this->validated($input, ['note' => ['nullable', 'string', 'max:1000']])
            + ['decision' => 'approved'];
    }

    public function summary(DealStep $step, array $payload): string
    {
        return $this->actorLabel($step).' approved '.$step->name.'.';
    }
}
