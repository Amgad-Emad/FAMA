<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\DealMessageResource;
use App\Http\Resources\DealResource;
use App\Http\Resources\DealStepResource;
use App\Models\Deal;
use App\Models\Talent;
use App\Services\DealInterventionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin deal intervention console (Phase 3B UI). Open any deal and override a
 * stuck step, act as the admin actor, nudge, reassign, or cancel — all through
 * DealInterventionService (which authorizes + audits). `can:intervene-deals`
 * gates the routes.
 */
class DealInterventionController extends AdminController
{
    private const STATUSES = ['awaiting_brand', 'awaiting_talent', 'awaiting_admin', 'completed', 'draft', 'cancelled', 'declined', 'expired'];

    private const ACTIONS = ['override', 'advance', 'nudge', 'reassign', 'cancel'];

    public function __construct(private readonly DealInterventionService $intervention) {}

    public function index(): View
    {
        return view('admin.deals.index');
    }

    public function data(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Deal::query()
            ->with(['brand', 'talent', 'service', 'currentStep'])
            ->latest();

        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(20);

        return response()->paginated($paginator, DealResource::collection($paginator->getCollection()));
    }

    public function show(Deal $deal): View
    {
        return view('admin.deals.show', ['deal' => $deal->load(['brand', 'talent'])]);
    }

    public function thread(Deal $deal): JsonResponse
    {
        $deal->load(['brand', 'talent', 'service', 'currentStep', 'steps', 'messages']);

        return response()->success([
            'deal' => new DealResource($deal),
            'steps' => DealStepResource::collection($deal->steps),
            'messages' => DealMessageResource::collection($deal->messages),
        ]);
    }

    public function act(Request $request, Deal $deal, string $action): JsonResponse
    {
        abort_unless(in_array($action, self::ACTIONS, true), 404);
        $admin = $this->admin();

        match ($action) {
            'override' => $this->intervention->overrideStep($admin, $deal, $request->input('note')),
            'advance' => $this->intervention->advanceAsAdmin($admin, $deal, $request->except('_token')),
            'nudge' => $this->intervention->nudge($admin, $deal, $request->validate(['note' => ['required', 'string', 'max:2000']])['note']),
            'cancel' => $this->intervention->cancel($admin, $deal, $request->input('reason')),
            'reassign' => $this->intervention->reassign(
                $admin,
                $deal,
                Talent::findOrFail($request->validate(['talent_id' => ['required', 'integer', 'exists:talents,id']])['talent_id']),
            ),
        };

        return response()->success(null, __('Intervention applied.'));
    }
}
