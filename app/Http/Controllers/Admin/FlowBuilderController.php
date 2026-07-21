<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StepRequest;
use App\Http\Requests\Admin\StoreFlowRequest;
use App\Http\Resources\ContractFlowResource;
use App\Models\ContractFlow;
use App\Models\ContractFlowStep;
use App\Services\ContractFlowBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin contract-flow builder (Phase 3B UI). Thin — delegates every mutation to
 * ContractFlowBuilderService (which authorizes + audits). `can:manage-flows` gates
 * the routes.
 */
class FlowBuilderController extends AdminController
{
    public function __construct(private readonly ContractFlowBuilderService $builder) {}

    public function index(): View
    {
        return view('admin.flows.index');
    }

    public function data(): JsonResponse
    {
        $paginator = ContractFlow::query()->withCount(['steps', 'contracts'])->latest()->paginate(20);

        return response()->paginated($paginator, ContractFlowResource::collection($paginator->getCollection()));
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

    public function show(ContractFlow $flow): View
    {
        return view('admin.flows.show', ['flow' => $flow]);
    }

    public function showData(ContractFlow $flow): JsonResponse
    {
        $flow->load('steps')->loadCount('contracts');

        return response()->success(['flow' => new ContractFlowResource($flow)]);
    }

    public function update(StoreFlowRequest $request, ContractFlow $flow): JsonResponse
    {
        $this->builder->updateFlow($this->admin(), $flow, $request->validated());

        return response()->success(null, __('Flow updated.'));
    }

    public function addStep(StepRequest $request, ContractFlow $flow): JsonResponse
    {
        $step = $this->builder->addStep($this->admin(), $flow, $request->validated());

        return response()->success(['id' => $step->id], __('Step added.'), status: 201);
    }

    public function updateStep(StepRequest $request, ContractFlow $flow, ContractFlowStep $step): JsonResponse
    {
        $this->builder->updateStep($this->admin(), $step, $request->validated());

        return response()->success(null, __('Step updated.'));
    }

    public function removeStep(ContractFlow $flow, ContractFlowStep $step): JsonResponse
    {
        $this->builder->removeStep($this->admin(), $step);

        return response()->success(null, __('Step removed.'));
    }

    public function reorderSteps(Request $request, ContractFlow $flow): JsonResponse
    {
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);
        $this->builder->reorderSteps($this->admin(), $flow, $data['ids']);

        return response()->success(null, __('Steps reordered.'));
    }

    public function markDefault(ContractFlow $flow): JsonResponse
    {
        $this->builder->markDefault($this->admin(), $flow);

        return response()->success(null, __('Set as the default flow.'));
    }

    public function activate(ContractFlow $flow): JsonResponse
    {
        $flow = $this->builder->activate($this->admin(), $flow);

        return response()->success(['status' => $flow->status->getValue()], __('Flow activated.'));
    }

    public function archive(ContractFlow $flow): JsonResponse
    {
        $flow = $this->builder->archive($this->admin(), $flow);

        return response()->success(['status' => $flow->status->getValue()], __('Flow archived.'));
    }
}
