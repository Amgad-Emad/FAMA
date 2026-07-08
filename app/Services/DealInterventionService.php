<?php

namespace App\Services;

use App\Deals\DealProgression;
use App\Models\Deal;
use App\Models\Talent;
use App\Models\User;
use App\States\Deal\Cancelled;
use InvalidArgumentException;

/**
 * Admin deal intervention (Phase 3A). Reuses the Phase 1E engine: the admin acts
 * as the `admin` actor (which the engine already permits on any step), overrides
 * a stuck step, nudges, reassigns, or cancels. Gated on `intervene-deals`; every
 * override is transactional, posts a system event, and is activity-logged.
 */
class DealInterventionService extends AdminService
{
    public function __construct(
        private readonly DealService $deals,
        private readonly DealProgression $progression,
    ) {}

    /**
     * Act as the admin actor on the current step (e.g. an approval/info step that
     * requires admin). Delegates to the shared engine with role = admin.
     *
     * @param  array<string, mixed>  $input
     */
    public function advanceAsAdmin(User $admin, Deal $deal, array $input = []): Deal
    {
        $this->authorizeAdmin($admin, 'intervene', $deal);

        $result = $this->deals->advance($deal, $input, 'admin', $admin);
        $this->record($admin, $result, 'deal_intervention', 'deal.advanced_by_admin');

        return $result;
    }

    /**
     * Force-complete the current (stuck) step regardless of its actor/handler,
     * then progress the deal.
     */
    public function overrideStep(User $admin, Deal $deal, ?string $note = null): Deal
    {
        $this->authorizeAdmin($admin, 'intervene', $deal);

        return $this->runInTransaction(function () use ($admin, $deal, $note): Deal {
            $step = $deal->currentStep;
            if ($step === null) {
                throw new InvalidArgumentException('This deal has no active step to override.');
            }

            $this->progression->finishStep($deal, $step, [], $admin, $note ?? "Admin override: completed {$step->name}.");
            $this->record($admin, $deal, 'deal_intervention', 'deal.step_overridden', ['step' => $step->key, 'note' => $note]);
            $this->progression->activateNext($deal);

            return $deal->refresh();
        }, ['deal_id' => $deal->getKey()]);
    }

    public function nudge(User $admin, Deal $deal, string $note): Deal
    {
        $this->authorizeAdmin($admin, 'intervene', $deal);

        return $this->runInTransaction(function () use ($admin, $deal, $note): Deal {
            $this->progression->postSystemEvent($deal, $deal->currentStep, "Admin nudge: {$note}");
            $this->record($admin, $deal, 'deal_intervention', 'deal.nudged', ['note' => $note]);

            return $deal;
        }, ['deal_id' => $deal->getKey()]);
    }

    public function reassign(User $admin, Deal $deal, Talent $talent): Deal
    {
        $this->authorizeAdmin($admin, 'intervene', $deal);

        return $this->runInTransaction(function () use ($admin, $deal, $talent): Deal {
            $previous = $deal->talent_id;
            $deal->update(['talent_id' => $talent->getKey()]);
            $this->progression->postSystemEvent($deal, null, 'Admin reassigned this deal.');
            $this->record($admin, $deal, 'deal_intervention', 'deal.reassigned', ['from' => $previous, 'to' => $talent->getKey()]);

            return $deal->refresh();
        }, ['deal_id' => $deal->getKey()]);
    }

    public function cancel(User $admin, Deal $deal, ?string $reason = null): Deal
    {
        $this->authorizeAdmin($admin, 'intervene', $deal);

        return $this->runInTransaction(function () use ($admin, $deal, $reason): Deal {
            $this->progression->moveDealTo($deal, Cancelled::class);
            $this->progression->postSystemEvent($deal, null, 'Admin cancelled the deal.'.($reason ? " Reason: {$reason}" : ''));
            $this->record($admin, $deal, 'deal_intervention', 'deal.cancelled_by_admin', ['reason' => $reason]);

            return $deal->refresh();
        }, ['deal_id' => $deal->getKey()]);
    }
}
