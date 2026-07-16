<?php

namespace App\Contracting;

use App\Contracting\Steps\ApprovalStepHandler;
use App\Contracting\Steps\ContractStepHandler;
use App\Contracting\Steps\FormStepHandler;
use App\Contracting\Steps\InfoStepHandler;
use App\Contracting\Steps\MessageStepHandler;
use App\Contracting\Steps\PaymentStepHandler;
use App\Contracting\Steps\ScheduleStepHandler;
use App\Contracting\Steps\StepHandler;
use App\Contracting\Steps\UploadStepHandler;
use App\Models\ContractStep;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * Factory for the step-type Strategy (pattern map). Resolves the StepHandler for
 * a `step_type` from the container so handlers can be injected/decorated. Adding
 * a step type = registering one handler here — no engine or schema change.
 */
class StepHandlerFactory
{
    /**
     * @var array<string, class-string<StepHandler>>
     */
    private array $handlers = [
        'form' => FormStepHandler::class,
        'approval' => ApprovalStepHandler::class,
        'upload' => UploadStepHandler::class,
        'payment' => PaymentStepHandler::class,
        'contract' => ContractStepHandler::class,
        'message' => MessageStepHandler::class,
        'schedule' => ScheduleStepHandler::class,
        'info' => InfoStepHandler::class,
    ];

    public function __construct(private readonly Container $container) {}

    public function for(string $stepType): StepHandler
    {
        $class = $this->handlers[$stepType]
            ?? throw new InvalidArgumentException("No step handler registered for type [{$stepType}].");

        return $this->container->make($class);
    }

    public function forStep(ContractStep $step): StepHandler
    {
        return $this->for($step->step_type);
    }
}
