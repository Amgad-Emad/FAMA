<?php

namespace App\Contracting\Steps;

use App\Models\ContractStep;

/**
 * approval — the actor approves the previous step's output. Approving advances
 * the contract; rejecting is a separate path (ContractService::rejectStep → loop back).
 */
class ApprovalStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'approval';
    }

    public function validate(ContractStep $step, array $input): array
    {
        return $this->validated($input, ['note' => ['nullable', 'string', 'max:1000']])
            + ['decision' => 'approved'];
    }

    public function summary(ContractStep $step, array $payload): string
    {
        return $this->actorLabel($step).' approved '.$step->name.'.';
    }
}
