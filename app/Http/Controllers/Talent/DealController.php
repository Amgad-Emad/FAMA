<?php

namespace App\Http\Controllers\Talent;

use App\Http\Resources\DealMessageResource;
use App\Http\Resources\DealResource;
use App\Http\Resources\DealStepResource;
use App\Models\Deal;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Talent deal room + inbox (talent-spec). The talent acts as the `talent` role.
 * Every mutation delegates to DealService; ownership is enforced per deal.
 */
class DealController extends TalentController
{
    public function __construct(private readonly DealService $deals) {}

    public function index(): View
    {
        return view('talent.deals.index');
    }

    public function data(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Deal::forTalent($this->talent()->getKey())
            ->with(['brand', 'currentStep'])
            // Count the brand's unread free-messages so the inbox can badge them.
            ->withCount(['messages as unread_count' => fn ($q) => $q->humanUnreadFor('talent')])
            ->latest();

        if (in_array($status, ['awaiting_talent', 'awaiting_brand', 'awaiting_admin', 'completed', 'draft', 'cancelled', 'declined', 'expired'], true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(15);

        return response()->paginated($paginator, DealResource::collection($paginator->getCollection()));
    }

    public function show(Deal $deal): View
    {
        $this->ensureOwns($deal);

        return view('talent.deals.show', ['deal' => $deal->load('brand')]);
    }

    /**
     * The deal room payload — header, stepper, timeline, and whose-turn — used
     * for the initial load and Ajax refresh. Reading the room marks the brand's
     * messages read.
     */
    public function thread(Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);

        $this->deals->markThreadRead($deal, 'talent');

        $deal->load(['brand', 'currentStep', 'steps', 'messages']);

        return response()->success([
            'deal' => new DealResource($deal),
            'steps' => DealStepResource::collection($deal->steps),
            'messages' => DealMessageResource::collection($deal->messages),
            'can_act' => $deal->currentStep?->actionableBy('talent') ?? false,
        ]);
    }

    public function advance(Request $request, Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->advance($deal, $request->except('_token'), 'talent', $this->talent());

        return response()->success(null, __('Step completed.'));
    }

    public function reject(Request $request, Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->reject($deal, 'talent', $request->input('reason'), $this->talent());

        return response()->success(null, __('Sent back for revision.'));
    }

    public function skip(Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->skip($deal, 'talent', $this->talent());

        return response()->success(null, __('Step skipped.'));
    }

    public function message(Request $request, Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $this->deals->postMessage($deal, 'talent', $this->talent(), $data['body']);

        return response()->success(null, __('Message sent.'));
    }
}
