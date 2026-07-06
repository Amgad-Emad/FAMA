<?php

namespace App\Deals\Steps;

use App\Models\Deal;
use App\Models\DealStep;

/**
 * form — collect structured answers (brief, quote). If the step declares an
 * `amount_field`, its value becomes the deal's agreed amount.
 */
class FormStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'form';
    }

    public function validate(DealStep $step, array $input): array
    {
        $rules = ['fields' => ['required', 'array']];

        if ($amountField = $this->setting($step, 'amount_field')) {
            $rules["fields.{$amountField}"] = ['required', 'numeric', 'min:0'];
        }

        return $this->validated($input, $rules);
    }

    public function apply(Deal $deal, DealStep $step, array $payload): void
    {
        $amountField = $this->setting($step, 'amount_field');

        if ($amountField && isset($payload['fields'][$amountField])) {
            $deal->agreed_amount = $payload['fields'][$amountField];
            $deal->save();
        }
    }

    public function summary(DealStep $step, array $payload): string
    {
        return $this->actorLabel($step).' submitted '.$step->name.'.';
    }
}
