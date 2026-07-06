<?php

namespace App\Deals\Steps;

use App\Models\DealStep;

/**
 * upload — the actor delivers files. `attachments` references the uploaded media
 * (ids / paths); the media itself is handled by medialibrary on the deal.
 */
class UploadStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'upload';
    }

    public function validate(DealStep $step, array $input): array
    {
        return $this->validated($input, [
            'attachments' => ['required', 'array', 'min:1'],
        ]);
    }

    public function summary(DealStep $step, array $payload): string
    {
        $count = count($payload['attachments'] ?? []);

        return $this->actorLabel($step).' delivered '.$count.' file'.($count === 1 ? '' : 's').' for '.$step->name.'.';
    }
}
