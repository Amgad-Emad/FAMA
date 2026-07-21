<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\ContractMessageResource;
use App\Http\Resources\ContractResource;
use App\Http\Resources\ContractStepResource;
use App\Models\Contract;
use App\Models\ContractStep;
use App\Models\Talent;
use App\Services\ContractInterventionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin contract intervention console (Phase 3B UI). Open any contract and override a
 * stuck step, act as the admin actor, nudge, reassign, or cancel — all through
 * ContractInterventionService (which authorizes + audits). `can:intervene-contracts`
 * gates the routes.
 */
class ContractInterventionController extends AdminController
{
    private const STATUSES = ['awaiting_brand', 'awaiting_talent', 'awaiting_admin', 'completed', 'draft', 'cancelled', 'declined', 'expired'];

    private const ACTIONS = ['override', 'advance', 'nudge', 'reassign', 'cancel'];

    public function __construct(private readonly ContractInterventionService $intervention) {}

    /**
     * The console shell. Step keys feed the current-step filter (one distinct
     * query over the snapshotted contract_steps).
     */
    public function index(): View
    {
        return view('admin.contracts.index', [
            'stepKeys' => ContractStep::query()->distinct()->orderBy('key')->pluck('key'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $step = $request->query('step');

        // `talent.media` is eager-loaded because ContractResource reads the talent's
        // `avatar_url`, which resolves through medialibrary — without it that accessor
        // fires one media query per row (medialibrary uses loadMissing, so
        // preventLazyLoading never surfaces it).
        $query = Contract::query()
            ->with(['brand', 'talent.media', 'currentStep'])
            ->latest();

        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        // Filter by the CURRENT step's key (e.g. every contract stuck on `payment`).
        if (is_string($step) && $step !== '') {
            $query->whereHas('currentStep', fn ($q) => $q->where('key', $step));
        }

        $paginator = $query->paginate(20);

        return response()->paginated($paginator, ContractResource::collection($paginator->getCollection()));
    }

    public function show(Contract $contract): View
    {
        return view('admin.contracts.show', ['contract' => $contract->load(['brand', 'talent'])]);
    }

    public function thread(Contract $contract): JsonResponse
    {
        $contract->load(['brand', 'talent', 'currentStep', 'steps', 'messages']);

        return response()->success([
            'contract' => new ContractResource($contract),
            'steps' => ContractStepResource::collection($contract->steps),
            'messages' => ContractMessageResource::collection($contract->messages),
        ]);
    }

    public function act(Request $request, Contract $contract, string $action): JsonResponse
    {
        abort_unless(in_array($action, self::ACTIONS, true), 404);
        $admin = $this->admin();

        match ($action) {
            'override' => $this->intervention->overrideStep($admin, $contract, $request->input('note')),
            'advance' => $this->intervention->advanceAsAdmin($admin, $contract, $request->except('_token')),
            'nudge' => $this->intervention->nudge($admin, $contract, $request->validate(['note' => ['required', 'string', 'max:2000']])['note']),
            'cancel' => $this->intervention->cancel($admin, $contract, $request->input('reason')),
            'reassign' => $this->intervention->reassign(
                $admin,
                $contract,
                Talent::findOrFail($request->validate(['talent_id' => ['required', 'integer', 'exists:talents,id']])['talent_id']),
            ),
        };

        return response()->success(null, __('Intervention applied.'));
    }
}
