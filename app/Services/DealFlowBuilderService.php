<?php

namespace App\Services;

use App\Models\DealFlow;
use App\Models\DealFlowStep;
use App\Models\User;
use App\States\DealFlow\Active;
use App\States\DealFlow\Archived;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Admin deal-flow builder (Phase 3A). Authors flow templates + their ordered
 * steps and drives the template lifecycle (draft → active → archived, with an
 * orthogonal default flag scoped by applies_to). Edits touch FUTURE deals only —
 * deals snapshot their steps at creation (Phase 1E). Gated on `manage-flows`;
 * DealFlow/DealFlowStep changes are audited by their LogsActivity traits.
 */
class DealFlowBuilderService extends AdminService
{
    private const STEP_FIELDS = ['key', 'name', 'instructions', 'actor', 'step_type', 'position', 'is_required', 'is_skippable', 'settings'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFlow(User $admin, array $data): DealFlow
    {
        $this->authorizeAdmin($admin, 'manage', DealFlow::class);

        return $this->runInTransaction(fn () => DealFlow::create([
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
     * @param  array<string, mixed>  $data
     */
    public function addStep(User $admin, DealFlow $flow, array $data): DealFlowStep
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
    public function updateStep(User $admin, DealFlowStep $step, array $data): DealFlowStep
    {
        $this->authorizeAdmin($admin, 'manage', $step->flow);

        return $this->runInTransaction(function () use ($step, $data): DealFlowStep {
            $step->update(Arr::only($data, self::STEP_FIELDS));

            return $step->refresh();
        }, ['step_id' => $step->getKey()]);
    }

    public function removeStep(User $admin, DealFlowStep $step): void
    {
        $this->authorizeAdmin($admin, 'manage', $step->flow);

        $this->runInTransaction(fn () => $step->delete(), ['step_id' => $step->getKey()]);
    }

    /**
     * @param  list<int>  $orderedIds  step ids in their new order
     */
    public function reorderSteps(User $admin, DealFlow $flow, array $orderedIds): void
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
    public function markDefault(User $admin, DealFlow $flow): DealFlow
    {
        $this->authorizeAdmin($admin, 'manage', $flow);

        return $this->runInTransaction(function () use ($flow): DealFlow {
            $others = DealFlow::query()->whereKeyNot($flow->getKey());
            $flow->applies_to === null
                ? $others->whereNull('applies_to')
                : $others->where('applies_to', $flow->applies_to);
            $others->update(['is_default' => false]);

            $flow->update(['is_default' => true]);

            return $flow->refresh();
        }, ['flow_id' => $flow->getKey()]);
    }

    public function activate(User $admin, DealFlow $flow): DealFlow
    {
        $this->authorizeAdmin($admin, 'manage', $flow);

        return $this->runInTransaction(function () use ($flow): DealFlow {
            $flow->status->transitionTo(Active::class); // syncs is_active = true

            return $flow->refresh();
        }, ['flow_id' => $flow->getKey()]);
    }

    /**
     * Retire a flow: archived + no longer the default. Existing deals are
     * unaffected (they run off their snapshot).
     */
    public function archive(User $admin, DealFlow $flow): DealFlow
    {
        $this->authorizeAdmin($admin, 'manage', $flow);

        return $this->runInTransaction(function () use ($flow): DealFlow {
            $flow->status->transitionTo(Archived::class); // syncs is_active = false
            $flow->update(['is_default' => false]);

            return $flow->refresh();
        }, ['flow_id' => $flow->getKey()]);
    }
}
