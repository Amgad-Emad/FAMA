<?php

namespace App\Contracting\Steps;

use App\Models\ContractStep;

/**
 * payment — a deposit or final leg. ADR-B: the final-payment automation boundary
 * is a per-step setting `confirmation` = manual | auto (default MANUAL). A manual
 * step waits for the payer to confirm; an auto step (or any system-actor step)
 * completes itself on activation.
 */
class PaymentStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'payment';
    }

    public function isAutomatic(ContractStep $step): bool
    {
        return parent::isAutomatic($step) || $this->confirmation($step) === 'auto';
    }

    public function validate(ContractStep $step, array $input): array
    {
        return $this->validated($input, [
            'confirmed' => ['accepted'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]) + ['percentage' => $this->setting($step, 'percentage')];
    }

    public function summary(ContractStep $step, array $payload): string
    {
        $pct = $payload['percentage'] ?? $this->setting($step, 'percentage');

        return $this->actorLabel($step).' paid'.($pct ? " the {$pct}% deposit" : '').'.';
    }

    /**
     * The confirmation mode for this step (ADR-B), defaulting to manual.
     */
    private function confirmation(ContractStep $step): string
    {
        return (string) $this->setting($step, 'confirmation', 'manual');
    }
}
