<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StepRequest;
use App\Http\Requests\Admin\StoreFlowRequest;
use App\Http\Resources\DealFlowResource;
use App\Models\DealFlow;
use App\Models\DealFlowStep;
use App\Services\DealFlowBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin deal-flow builder (Phase 3B UI). Thin — delegates every mutation to
 * DealFlowBuilderService (which authorizes + audits). `can:manage-flows` gates
 * the routes.
 */
class FlowBuilderController extends AdminController
{
    public function __construct(private readonly DealFlowBuilderService $builder) {}

    public function index(): View
    {
        return view('admin.flows.index');
    }

    public function data(): JsonResponse
    {
        $paginator = DealFlow::query()->withCount(['steps', 'deals'])->latest()->paginate(20);

        return response()->paginated($paginator, DealFlowResource::collection($paginator->getCollection()));
    }

    public function store(StoreFlowRequest $request): JsonResponse
    {
        $flow = $this->builder->createFlow($this->admin(), $request->validated());

        return response()->success(
            ['id' => $flow->id, 'redirect' => route('admin.flows.show', $flow)],
            __('Flow created.'),
            status: 201,
        );
    }

    public function show(DealFlow $flow): View
    {
        return view('admin.flows.show', ['flow' => $flow]);
    }

    public function showData(DealFlow $flow): JsonResponse
    {
        $flow->load('steps')->loadCount('deals');

        return response()->success(['flow' => new DealFlowResource($flow)]);
    }

    public function update(StoreFlowRequest $request, DealFlow $flow): JsonResponse
    {
        $this->builder->updateFlow($this->admin(), $flow, $request->validated());

        return response()->success(null, __('Flow updated.'));
    }

    public function addStep(StepRequest $request, DealFlow $flow): JsonResponse
    {
        $step = $this->builder->addStep($this->admin(), $flow, $request->validated());

        return response()->success(['id' => $step->id], __('Step added.'), status: 201);
    }

    public function updateStep(StepRequest $request, DealFlow $flow, DealFlowStep $step): JsonResponse
    {
        $this->builder->updateStep($this->admin(), $step, $request->validated());

        return response()->success(null, __('Step updated.'));
    }

    public function removeStep(DealFlow $flow, DealFlowStep $step): JsonResponse
    {
        $this->builder->removeStep($this->admin(), $step);

        return response()->success(null, __('Step removed.'));
    }

    public function reorderSteps(Request $request, DealFlow $flow): JsonResponse
    {
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);
        $this->builder->reorderSteps($this->admin(), $flow, $data['ids']);

        return response()->success(null, __('Steps reordered.'));
    }

    public function markDefault(DealFlow $flow): JsonResponse
    {
        $this->builder->markDefault($this->admin(), $flow);

        return response()->success(null, __('Set as the default flow.'));
    }

    public function activate(DealFlow $flow): JsonResponse
    {
        $flow = $this->builder->activate($this->admin(), $flow);

        return response()->success(['status' => $flow->status->getValue()], __('Flow activated.'));
    }

    public function archive(DealFlow $flow): JsonResponse
    {
        $flow = $this->builder->archive($this->admin(), $flow);

        return response()->success(['status' => $flow->status->getValue()], __('Flow archived.'));
    }
}
