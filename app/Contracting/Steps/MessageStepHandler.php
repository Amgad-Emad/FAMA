<?php

namespace App\Contracting\Steps;

use App\Models\ContractStep;

/**
 * message — a required back-and-forth beat: the actor must post a message to
 * advance. The body is captured and echoed into the summary.
 */
class MessageStepHandler extends AbstractStepHandler
{
    public function type(): string
    {
        return 'message';
    }

    public function validate(ContractStep $step, array $input): array
    {
        return $this->validated($input, [
            'body' => ['required', 'string', 'max:5000'],
        ]);
    }

    public function summary(ContractStep $step, array $payload): string
    {
        return $this->actorLabel($step).' sent a message.';
    }
}
