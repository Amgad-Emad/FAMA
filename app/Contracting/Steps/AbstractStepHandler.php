<?php

namespace App\Contracting\Steps;

use App\Models\Contract;
use App\Models\ContractStep;
use Illuminate\Support\Facades\Validator;

/**
 * Shared behaviour for step handlers: no-op side effects, "automatic iff the
 * actor is the system" by default, a generic summary, and validation helpers.
 * Concrete handlers override what differs.
 */
abstract class AbstractStepHandler implements StepHandler
{
    public function apply(Contract $contract, ContractStep $step, array $payload): void
    {
        // Most steps only record their payload; no contract-level side effect.
    }

    public function isAutomatic(ContractStep $step): bool
    {
        return $step->actor === 'system';
    }

    public function summary(ContractStep $step, array $payload): string
    {
        return $this->actorLabel($step).' completed '.$step->name.'.';
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function validated(array $input, array $rules): array
    {
        return Validator::make($input, $rules)->validate();
    }

    protected function setting(ContractStep $step, string $key, mixed $default = null): mixed
    {
        return data_get($step->settings, $key, $default);
    }

    protected function actorLabel(ContractStep $step): string
    {
        return $step->actor === 'system' ? 'System' : ucfirst($step->actor);
    }
}
