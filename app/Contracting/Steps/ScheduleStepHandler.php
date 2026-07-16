<?php

namespace App\Contracting\Steps;

use App\Models\Contract;
use App\Models\ContractStep;

/**
 * schedule — agree shoot dates. Writes start/end onto the contract's headline data.
 */
class ScheduleStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'schedule';
    }

    public function validate(ContractStep $step, array $input): array
    {
        return $this->validated($input, [
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }

    public function apply(Contract $contract, ContractStep $step, array $payload): void
    {
        $contract->start_date = $payload['start_date'];
        $contract->end_date = $payload['end_date'] ?? null;
        $contract->save();
    }

    public function summary(ContractStep $step, array $payload): string
    {
        return $this->actorLabel($step).' scheduled '.$step->name.' for '.($payload['start_date'] ?? '').'.';
    }
}
