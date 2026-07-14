<?php

namespace App\Http\Controllers\Brand;

use App\Http\Resources\DealMessageResource;
use App\Http\Resources\DealResource;
use App\Http\Resources\DealStepResource;
use App\Models\Deal;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Brand deal room + inbox (brand-spec) — the brand side of the shared deal
 * engine (Phase 1E). The brand acts as the `brand` role (submit brief, review /
 * accept quotes, sign, pay); `awaiting_brand` is highlighted. Every mutation
 * delegates to DealService; ownership is enforced per deal.
 */
class DealController extends BrandController
{
    public function __construct(private readonly DealService $deals) {}

    public function index(): View
    {
        return view('brand.deals.index');
    }

    public function data(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Deal::query()
            ->where('brand_id', $this->brand()->getKey())
            ->with(['talent', 'currentStep'])
            ->latest();

        if (in_array($status, ['awaiting_brand', 'awaiting_talent', 'awaiting_admin', 'completed', 'draft', 'cancelled', 'declined', 'expired'], true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(15);

        return response()->paginated($paginator, DealResource::collection($paginator->getCollection()));
    }

    public function show(Deal $deal): View
    {
        $this->ensureOwns($deal);

        return view('brand.deals.show', ['deal' => $deal->load('talent')]);
    }

    public function thread(Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->markThreadRead($deal, 'brand');

        $deal->load(['talent', 'currentStep', 'steps', 'messages']);

        return response()->success([
            'deal' => new DealResource($deal),
            'steps' => DealStepResource::collection($deal->steps),
            'messages' => DealMessageResource::collection($deal->messages),
            'can_act' => $deal->currentStep?->actionableBy('brand') ?? false,
        ]);
    }

    public function advance(Request $request, Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->advance($deal, $request->except('_token'), 'brand', $this->brand());

        return response()->success(null, __('Step completed.'));
    }

    public function reject(Request $request, Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->reject($deal, 'brand', $request->input('reason'), $this->brand());

        return response()->success(null, __('Sent back for revision.'));
    }

    public function skip(Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->skip($deal, 'brand', $this->brand());

        return response()->success(null, __('Step skipped.'));
    }

    public function message(Request $request, Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $this->deals->postMessage($deal, 'brand', $this->brand(), $data['body']);

        return response()->success(null, __('Message sent.'));
    }
}
