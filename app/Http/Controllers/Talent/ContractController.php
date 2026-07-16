<?php

namespace App\Http\Controllers\Talent;

use App\Http\Resources\ContractMessageResource;
use App\Http\Resources\ContractResource;
use App\Http\Resources\ContractStepResource;
use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Talent contract room + inbox (talent-spec). The talent acts as the `talent` role.
 * Every mutation delegates to ContractService; ownership is enforced per contract.
 */
class ContractController extends TalentController
{
    public function __construct(private readonly ContractService $contracts) {}

    public function index(): View
    {
        return view('talent.contracts.index');
    }

    public function data(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Contract::forTalent($this->talent()->getKey())
            ->with(['brand', 'currentStep'])
            // Count the brand's unread free-messages so the inbox can badge them.
            ->withCount(['messages as unread_count' => fn ($q) => $q->humanUnreadFor('talent')])
            ->latest();

        if (in_array($status, ['awaiting_talent', 'awaiting_brand', 'awaiting_admin', 'completed', 'draft', 'cancelled', 'declined', 'expired'], true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(15);

        return response()->paginated($paginator, ContractResource::collection($paginator->getCollection()));
    }

    public function show(Contract $contract): View
    {
        $this->ensureOwns($contract);

        return view('talent.contracts.show', ['contract' => $contract->load('brand')]);
    }

    /**
     * The contract room payload — header, stepper, timeline, and whose-turn — used
     * for the initial load and Ajax refresh. Reading the room marks the brand's
     * messages read.
     */
    public function thread(Contract $contract): JsonResponse
    {
        $this->ensureOwns($contract);

        $this->contracts->markThreadRead($contract, 'talent');

        $contract->load(['brand', 'currentStep', 'steps', 'messages.media']);

        return response()->success([
            'contract' => new ContractResource($contract),
            'steps' => ContractStepResource::collection($contract->steps),
            'messages' => ContractMessageResource::collection($contract->messages),
            'can_act' => $contract->currentStep?->actionableBy('talent') ?? false,
        ]);
    }

    public function advance(Request $request, Contract $contract): JsonResponse
    {
        $this->ensureOwns($contract);
        $this->contracts->advance($contract, $request->except('_token'), 'talent', $this->talent());

        return response()->success(null, __('Step completed.'));
    }

    public function reject(Request $request, Contract $contract): JsonResponse
    {
        $this->ensureOwns($contract);
        $this->contracts->reject($contract, 'talent', $request->input('reason'), $this->talent());

        return response()->success(null, __('Sent back for revision.'));
    }

    public function skip(Contract $contract): JsonResponse
    {
        $this->ensureOwns($contract);
        $this->contracts->skip($contract, 'talent', $this->talent());

        return response()->success(null, __('Step skipped.'));
    }

    public function message(Request $request, Contract $contract): JsonResponse
    {
        $this->ensureOwns($contract);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $this->contracts->postMessage($contract, 'talent', $this->talent(), $data['body']);

        return response()->success(null, __('Message sent.'));
    }
}
