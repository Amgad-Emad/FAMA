<?php

namespace App\Http\Controllers\Brand;

use App\Http\Resources\ContractMessageResource;
use App\Http\Resources\ContractResource;
use App\Http\Resources\ContractStepResource;
use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Brand contract room + inbox (brand-spec) — the brand side of the shared contract
 * engine (Phase 1E). The brand acts as the `brand` role (submit brief, review /
 * accept quotes, sign, pay); `awaiting_brand` is highlighted. Every mutation
 * delegates to ContractService; ownership is enforced per contract.
 */
class ContractController extends BrandController
{
    public function __construct(private readonly ContractService $contracts) {}

    public function index(): View
    {
        return view('brand.contracts.index');
    }

    public function data(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Contract::query()
            ->where('brand_id', $this->brand()->getKey())
            ->with(['talent', 'currentStep'])
            // Count the counterparty's unread free-messages so the inbox can badge them.
            ->withCount(['messages as unread_count' => fn ($q) => $q->humanUnreadFor('brand')])
            ->latest();

        if (in_array($status, ['awaiting_brand', 'awaiting_talent', 'awaiting_admin', 'completed', 'draft', 'cancelled', 'declined', 'expired'], true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(15);

        return response()->paginated($paginator, ContractResource::collection($paginator->getCollection()));
    }

    public function show(Contract $contract): View
    {
        $this->ensureOwns($contract);

        return view('brand.contracts.show', ['contract' => $contract->load('talent')]);
    }

    public function thread(Contract $contract): JsonResponse
    {
        $this->ensureOwns($contract);
        $this->contracts->markThreadRead($contract, 'brand');

        $contract->load(['talent', 'project', 'currentStep', 'steps', 'messages.media']);

        return response()->success([
            'contract' => new ContractResource($contract),
            'steps' => ContractStepResource::collection($contract->steps),
            'messages' => ContractMessageResource::collection($contract->messages),
            'can_act' => $contract->currentStep?->actionableBy('brand') ?? false,
        ]);
    }

    public function advance(Request $request, Contract $contract): JsonResponse
    {
        $this->ensureOwns($contract);
        $this->contracts->advance($contract, $request->except('_token'), 'brand', $this->brand());

        return response()->success(null, __('Step completed.'));
    }

    public function reject(Request $request, Contract $contract): JsonResponse
    {
        $this->ensureOwns($contract);
        $this->contracts->reject($contract, 'brand', $request->input('reason'), $this->brand());

        return response()->success(null, __('Sent back for revision.'));
    }

    public function skip(Contract $contract): JsonResponse
    {
        $this->ensureOwns($contract);
        $this->contracts->skip($contract, 'brand', $this->brand());

        return response()->success(null, __('Step skipped.'));
    }

    public function message(Request $request, Contract $contract): JsonResponse
    {
        $this->ensureOwns($contract);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $this->contracts->postMessage($contract, 'brand', $this->brand(), $data['body']);

        return response()->success(null, __('Message sent.'));
    }
}
