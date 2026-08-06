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

        // Every DECLARED field gets a passthrough rule so it survives validation
        // (Validator::validated() strips keys that have no rule) — otherwise the
        // `writes` map below would never see them.
        foreach ((array) $this->setting($step, 'fields', []) as $field) {
            $rules["fields.{$field}"] = ['nullable'];
        }

        if ($amountField = $this->setting($step, 'amount_field')) {
            $rules["fields.{$amountField}"] = ['required', 'numeric', 'min:0'];
        }

        // Any field that writes to a date column is validated as a date so the
        // downstream contract cast never chokes on free text.
        foreach ((array) $this->setting($step, 'writes', []) as $field => $column) {
            if (str_ends_with((string) $column, '_date')) {
                $rules["fields.{$field}"] = ['nullable', 'date'];
            }
        }

        return $this->validated($input, $rules);
    }

    /**
     * Persist the step's answers onto the contract's headline columns so later
     * steps (e.g. the contract document) can render them. `amount_field` feeds the
     * agreed amount; the optional `writes` map copies named fields into contract
     * columns (scope → brief, start_date/end_date → the date columns).
     */
    public function apply(Contract $contract, ContractStep $step, array $payload): void
    {
        $fields = $payload['fields'] ?? [];

        if (($amountField = $this->setting($step, 'amount_field')) && isset($fields[$amountField])) {
            $contract->agreed_amount = $fields[$amountField];
        }

        foreach ((array) $this->setting($step, 'writes', []) as $field => $column) {
            if (array_key_exists($field, $fields)) {
                $contract->{$column} = $fields[$field] === '' ? null : $fields[$field];
            }
        }

        if ($contract->isDirty()) {
            $contract->save();
        }
    }

    public function summary(ContractStep $step, array $payload): string
    {
        return $this->actorLabel($step).' submitted '.$step->name.'.';
    }
}
