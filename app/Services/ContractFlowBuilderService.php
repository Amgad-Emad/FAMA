<?php

namespace App\Services;

use App\Models\ContractFlow;
use App\Models\ContractFlowStep;
use App\Models\User;
use App\States\ContractFlow\Active;
use App\States\ContractFlow\Archived;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Admin contract-flow builder (Phase 3A). Authors flow templates + their ordered
 * steps and drives the template lifecycle (draft → active → archived, with an
 * orthogonal default flag scoped by applies_to). Edits touch FUTURE contracts only —
 * contracts snapshot their steps at creation (Phase 1E). Gated on `manage-flows`;
 * ContractFlow/ContractFlowStep changes are audited by their LogsActivity traits.
 */
class ContractFlowBuilderService extends AdminService
{
    private const STEP_FIELDS = ['key', 'name', 'instructions', 'actor', 'step_type', 'position', 'is_required', 'is_skippable', 'settings'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFlow(User $admin, array $data): ContractFlow
    {
        $this->authorizeAdmin($admin, 'manage', ContractFlow::class);

        return $this->runInTransaction(fn () => ContractFlow::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']).'-'.Str::lower(Str::random(6)),
            'description' => $data['description'] ?? null,
            'applies_to' => $data['applies_to'] ?? null,
            'status' => 'draft',
            'is_active' => false,
            'is_default' => false,
        ]), ['admin_id' => $admin->getKey()]);
    }

    /**
     * Edit a flow's metadata (name / description / applies_to scope).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateFlow(User $admin, ContractFlow $flow, array $data): ContractFlow
    {
        $this->authorizeAdmin($admin, 'manage', $flow);

        return $this->runInTransaction(function () use ($flow, $data): ContractFlow {
            $flow->update(Arr::only($data, ['name', 'description', 'applies_to']));

            return $flow->refresh();
        }, ['flow_id' => $flow->getKey()]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addStep(User $admin, ContractFlow $flow, array $data): ContractFlowStep
    {
        $this->authorizeAdmin($admin, 'manage', $flow);

        return $this->runInTransaction(fn () => $flow->steps()->create(
            Arr::only($data, self::STEP_FIELDS) + [
                'position' => $data['position'] ?? $flow->steps()->count(),
                'is_required' => $data['is_required'] ?? true,
                'is_skippable' => $data['is_skippable'] ?? false,
                'settings' => $data['settings'] ?? [],
            ]
        ), ['flow_id' => $flow->getKey()]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStep(User $admin, ContractFlowStep $step, array $data): ContractFlowStep
    {
        $this->authorizeAdmin($admin, 'manage', $step->flow);

        return $this->runInTransaction(function () use ($step, $data): ContractFlowStep {
            $step->update(Arr::only($data, self::STEP_FIELDS));

            return $step->refresh();
        }, ['step_id' => $step->getKey()]);
    }

    public function removeStep(User $admin, ContractFlowStep $step): void
    {
        $this->authorizeAdmin($admin, 'manage', $step->flow);

        $this->runInTransaction(fn () => $step->delete(), ['step_id' => $step->getKey()]);
    }

    /**
     * @param  list<int>  $orderedIds  step ids in their new order
     */
    public function reorderSteps(User $admin, ContractFlow $flow, array $orderedIds): void
    {
        $this->authorizeAdmin($admin, 'manage', $flow);

        $this->runInTransaction(function () use ($flow, $orderedIds): void {
            foreach (array_values($orderedIds) as $position => $id) {
                $flow->steps()->whereKey($id)->update(['position' => $position]);
            }
        }, ['flow_id' => $flow->getKey()]);
    }

    /**
     * Mark this flow the single default for its applies_to scope.
     */
    public function markDefault(User $admin, ContractFlow $flow): ContractFlow
    {
        $this->authorizeAdmin($admin, 'manage', $flow);

        return $this->runInTransaction(function () use ($flow): ContractFlow {
            $others = ContractFlow::query()->whereKeyNot($flow->getKey());
            $flow->applies_to === null
                ? $others->whereNull('applies_to')
                : $others->where('applies_to', $flow->applies_to);
            $others->update(['is_default' => false]);

            $flow->update(['is_default' => true]);

            return $flow->refresh();
        }, ['flow_id' => $flow->getKey()]);
    }

    public function activate(User $admin, ContractFlow $flow): ContractFlow
    {
        $this->authorizeAdmin($admin, 'manage', $flow);

        return $this->runInTransaction(function () use ($flow): ContractFlow {
            $flow->status->transitionTo(Active::class); // syncs is_active = true

            return $flow->refresh();
        }, ['flow_id' => $flow->getKey()]);
    }

    /**
     * Retire a flow: archived + no longer the default. Existing contracts are
     * unaffected (they run off their snapshot).
     */
    public function archive(User $admin, ContractFlow $flow): ContractFlow
    {
        $this->authorizeAdmin($admin, 'manage', $flow);

        return $this->runInTransaction(function () use ($flow): ContractFlow {
            $flow->status->transitionTo(Archived::class); // syncs is_active = false
            $flow->update(['is_default' => false]);

            return $flow->refresh();
        }, ['flow_id' => $flow->getKey()]);
    }
}
