<?php

namespace App\Deals;

use App\Deals\Steps\ApprovalStepHandler;
use App\Deals\Steps\ContractStepHandler;
use App\Deals\Steps\FormStepHandler;
use App\Deals\Steps\InfoStepHandler;
use App\Deals\Steps\MessageStepHandler;
use App\Deals\Steps\PaymentStepHandler;
use App\Deals\Steps\ScheduleStepHandler;
use App\Deals\Steps\StepHandler;
use App\Deals\Steps\UploadStepHandler;
use App\Models\DealStep;
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

    public function forStep(DealStep $step): StepHandler
    {
        return $this->for($step->step_type);
    }
}
