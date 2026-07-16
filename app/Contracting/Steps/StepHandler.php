<?php

namespace App\Contracting\Steps;

use App\Models\Contract;
use App\Models\ContractStep;

/**
 * Strategy contract for a contract step (talent-spec "contract loop"; pattern map →
 * Strategy + Factory). One implementation per `step_type`. The engine
 * (ContractService/AdvanceContract) calls validate() → apply() → summary() when the
 * step's actor completes it; isAutomatic() lets a step (e.g. a system info step
 * or an auto-confirmed payment) complete itself on activation.
 */
interface StepHandler
{
    /**
     * The step_type this handler serves.
     */
    public function type(): string;

    /**
     * Validate the actor's input and return the payload to persist on the step.
     * Throws Illuminate\Validation\ValidationException on bad input (→ 422).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validate(ContractStep $step, array $input): array;

    /**
     * Apply the step's side effects to the contract (e.g. set agreed_amount or
     * shoot dates). Runs inside the engine's transaction.
     *
     * @param  array<string, mixed>  $payload
     */
    public function apply(Contract $contract, ContractStep $step, array $payload): void;

    /**
     * Should this step complete automatically on activation (no human action)?
     */
    public function isAutomatic(ContractStep $step): bool;

    /**
     * A human, past-tense summary of the completed step for the timeline
     * (posted as a system_event).
     *
     * @param  array<string, mixed>  $payload
     */
    public function summary(ContractStep $step, array $payload): string;
}
