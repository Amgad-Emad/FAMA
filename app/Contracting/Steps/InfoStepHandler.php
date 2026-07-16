<?php

namespace App\Contracting\Steps;

use App\Models\ContractStep;

/**
 * info — a milestone / notice. A system-actor info step auto-completes (e.g. the
 * final "Contract complete" marker); a human-actor info step is an acknowledgement.
 */
class InfoStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'info';
    }

    public function validate(ContractStep $step, array $input): array
    {
        return $this->validated($input, []);
    }

    public function summary(ContractStep $step, array $payload): string
    {
        return $step->actor === 'system'
            ? $step->name.'.'
            : $this->actorLabel($step).' acknowledged '.$step->name.'.';
    }
}
