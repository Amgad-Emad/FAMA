<?php

namespace App\Deals\Steps;

use App\Models\Deal;
use App\Models\DealStep;

/**
 * schedule — agree shoot dates. Writes start/end onto the deal's headline data.
 */
class ScheduleStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'schedule';
    }

    public function validate(DealStep $step, array $input): array
    {
        return $this->validated($input, [
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }

    public function apply(Deal $deal, DealStep $step, array $payload): void
    {
        $deal->start_date = $payload['start_date'];
        $deal->end_date = $payload['end_date'] ?? null;
        $deal->save();
    }

    public function summary(DealStep $step, array $payload): string
    {
        return $this->actorLabel($step).' scheduled '.$step->name.' for '.($payload['start_date'] ?? '').'.';
    }
}
