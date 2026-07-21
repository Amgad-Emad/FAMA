<?php

namespace App\Services;

use App\Contracting\ContractProgression;
use App\Models\Contract;
use App\Models\Talent;
use App\Models\User;
use App\States\Contract\Cancelled;
use InvalidArgumentException;

/**
 * Admin contract intervention (Phase 3A). Reuses the Phase 1E engine: the admin acts
 * as the `admin` actor (which the engine already permits on any step), overrides
 * a stuck step, nudges, reassigns, or cancels. Gated on `intervene-contracts`; every
 * override is transactional, posts a system event, and is activity-logged.
 */
class ContractInterventionService extends AdminService
{
    public function __construct(
        private readonly ContractService $contracts,
        private readonly ContractProgression $progression,
    ) {}

    /**
     * Act as the admin actor on the current step (e.g. an approval/info step that
     * requires admin). Delegates to the shared engine with role = admin.
     *
     * @param  array<string, mixed>  $input
     */
    public function advanceAsAdmin(User $admin, Contract $contract, array $input = []): Contract
    {
        $this->authorizeAdmin($admin, 'intervene', $contract);

        $result = $this->contracts->advance($contract, $input, 'admin', $admin);
        $this->record($admin, $result, 'contract_intervention', 'contract.advanced_by_admin');

        return $result;
    }

    /**
     * Force-complete the current (stuck) step regardless of its actor/handler,
     * then progress the contract.
     */
    public function overrideStep(User $admin, Contract $contract, ?string $note = null): Contract
    {
        $this->authorizeAdmin($admin, 'intervene', $contract);

        return $this->runInTransaction(function () use ($admin, $contract, $note): Contract {
            $step = $contract->currentStep;
            if ($step === null) {
                throw new InvalidArgumentException('This contract has no active step to override.');
            }

            $this->progression->finishStep($contract, $step, [], $admin, $note ?? "Admin override: completed {$step->name}.");
            $this->record($admin, $contract, 'contract_intervention', 'contract.step_overridden', ['step' => $step->key, 'note' => $note]);
            $this->progression->activateNext($contract);

            return $contract->refresh();
        }, ['contract_id' => $contract->getKey()]);
    }

    public function nudge(User $admin, Contract $contract, string $note): Contract
    {
        $this->authorizeAdmin($admin, 'intervene', $contract);

        return $this->runInTransaction(function () use ($admin, $contract, $note): Contract {
            $this->progression->postSystemEvent($contract, $contract->currentStep, "Admin nudge: {$note}");
            $this->record($admin, $contract, 'contract_intervention', 'contract.nudged', ['note' => $note]);

            return $contract;
        }, ['contract_id' => $contract->getKey()]);
    }

    public function reassign(User $admin, Contract $contract, Talent $talent): Contract
    {
        $this->authorizeAdmin($admin, 'intervene', $contract);

        return $this->runInTransaction(function () use ($admin, $contract, $talent): Contract {
            $previous = $contract->talent_id;
            $contract->update(['talent_id' => $talent->getKey()]);
            $this->progression->postSystemEvent($contract, null, 'Admin reassigned this contract.');
            $this->record($admin, $contract, 'contract_intervention', 'contract.reassigned', ['from' => $previous, 'to' => $talent->getKey()]);

            return $contract->refresh();
        }, ['contract_id' => $contract->getKey()]);
    }

    public function cancel(User $admin, Contract $contract, ?string $reason = null): Contract
    {
        $this->authorizeAdmin($admin, 'intervene', $contract);

        return $this->runInTransaction(function () use ($admin, $contract, $reason): Contract {
            $this->progression->moveContractTo($contract, Cancelled::class);
            $this->progression->postSystemEvent($contract, null, 'Admin cancelled the contract.'.($reason ? " Reason: {$reason}" : ''));
            $this->record($admin, $contract, 'contract_intervention', 'contract.cancelled_by_admin', ['reason' => $reason]);

            return $contract->refresh();
        }, ['contract_id' => $contract->getKey()]);
    }
}
