<?php

namespace App\Contracting\Steps;

use App\Models\Contract;
use App\Models\ContractStep;

/**
 * form — collect structured answers (brief, quote). If the step declares an
 * `amount_field`, its value becomes the contract's agreed amount.
 */
class FormStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'form';
    }

    public function validate(ContractStep $step, array $input): array
    {
        $rules = ['fields' => ['required', 'array']];

        if ($amountField = $this->setting($step, 'amount_field')) {
            $rules["fields.{$amountField}"] = ['required', 'numeric', 'min:0'];
        }

        return $this->validated($input, $rules);
    }

    public function apply(Contract $contract, ContractStep $step, array $payload): void
    {
        $amountField = $this->setting($step, 'amount_field');

        if ($amountField && isset($payload['fields'][$amountField])) {
            $contract->agreed_amount = $payload['fields'][$amountField];
            $contract->save();
        }
    }

    public function summary(ContractStep $step, array $payload): string
    {
        return $this->actorLabel($step).' submitted '.$step->name.'.';
    }
}
