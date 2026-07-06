<?php

namespace App\Deals\Steps;

use App\Models\DealStep;

/**
 * info — a milestone / notice. A system-actor info step auto-completes (e.g. the
 * final "Deal complete" marker); a human-actor info step is an acknowledgement.
 */
class InfoStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'info';
    }

    public function validate(DealStep $step, array $input): array
    {
        return $this->validated($input, []);
    }

    public function summary(DealStep $step, array $payload): string
    {
        return $step->actor === 'system'
            ? $step->name.'.'
            : $this->actorLabel($step).' acknowledged '.$step->name.'.';
    }
}
