<?php

namespace App\Contracting\Steps;

use App\Models\ContractStep;

/**
 * upload — the actor delivers files. `attachments` references the uploaded media
 * (ids / paths); the media itself is handled by medialibrary on the contract.
 */
class UploadStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'upload';
    }

    public function validate(ContractStep $step, array $input): array
    {
        return $this->validated($input, [
            'attachments' => ['required', 'array', 'min:1'],
        ]);
    }

    public function summary(ContractStep $step, array $payload): string
    {
        $count = count($payload['attachments'] ?? []);

        return $this->actorLabel($step).' delivered '.$count.' file'.($count === 1 ? '' : 's').' for '.$step->name.'.';
    }
}
