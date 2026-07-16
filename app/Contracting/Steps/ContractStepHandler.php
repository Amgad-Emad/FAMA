<?php

namespace App\Contracting\Steps;

use App\Models\ContractStep;

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

    public function validate(ContractStep $step, array $input): array
    {
        return $this->validated($input, [
            'signed' => ['accepted'],
            'signatory' => ['nullable', 'string', 'max:255'],
        ]);
    }

    public function summary(ContractStep $step, array $payload): string
    {
        return $this->actorLabel($step).' signed '.$step->name.'.';
    }
}
