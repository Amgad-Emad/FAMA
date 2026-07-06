<?php

namespace App\Deals\Steps;

use App\Models\DealStep;

/**
 * contract — the actor signs an agreement. `settings.template` may reference a
 * contract template; here we record the signature.
 */
class ContractStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'contract';
    }

    public function validate(DealStep $step, array $input): array
    {
        return $this->validated($input, [
            'signed' => ['accepted'],
            'signatory' => ['nullable', 'string', 'max:255'],
        ]);
    }

    public function summary(DealStep $step, array $payload): string
    {
        return $this->actorLabel($step).' signed '.$step->name.'.';
    }
}
