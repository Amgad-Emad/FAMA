<?php

namespace App\Http\Controllers\Api\V1\Brand;

use App\Http\Requests\Brand\StartDealRequest;
use App\Http\Resources\DealMessageResource;
use App\Http\Resources\DealResource;
use App\Http\Resources\DealStepResource;
use App\Models\Deal;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Brand · Deals
 *
 * @authenticated
 *
 * The brand's side of the shared deal engine. The brand acts as the `brand` role
 * (submit brief, review/accept quotes, sign, pay); every mutation delegates to
 * DealService and is scoped to a deal the brand owns (403 otherwise). Step actions
 * are `advance` with the payload the current step's `step_type` expects.
 */
class DealController extends BrandApiController
{
    public function __construct(private readonly DealService $deals) {}

    /**
     * Start a deal
     *
     * Brand-initiated ("Start a deal"): creates a deal with the resolved active
     * flow, snapshots its steps, activates the first step and notifies the talent.
     * Guard failures (talent unpublished / not bookable, brand incomplete, no
     * active flow) return **422**. Optionally links a campaign (`deals.campaign_id`).
     *
     * @response 201 scenario="Created" {"success":true,"data":{"id":1,"reference":"FAMA-2026-00001","status":"awaiting_brand"},"message":"Deal started.","errors":null,"meta":{"room":"/api/v1/brand/deals/1"}}
     */
    public function store(StartDealRequest $request): JsonResponse
    {
        $deal = $this->deals->startBrandDeal($this->brand(), $request->payload());
        $deal->load(['brand', 'talent', 'service', 'currentStep']);

        return response()->success(
            new DealResource($deal),
            __('Deal started.'),
            ['room' => "/api/v1/brand/deals/{$deal->id}"],
            201,
        );
    }

    /**
     * List my deals
     *
     * Paginated inbox, newest first, with the counterparty + current step loaded.
     *
     * @queryParam status string Filter by deal status (e.g. awaiting_brand, completed). Example: awaiting_brand
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Deal::query()
            ->where('brand_id', $this->brand()->getKey())
            ->with(['talent', 'service', 'currentStep'])
            ->latest();

        if (in_array($status, ['awaiting_brand', 'awaiting_talent', 'awaiting_admin', 'completed', 'draft', 'cancelled', 'declined', 'expired'], true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(15);

        return response()->paginated($paginator, DealResource::collection($paginator->getCollection()));
    }

    /**
     * Get a deal room
     *
     * The deal, its stepper, the message timeline and whether the brand can act
     * on the current step. Reading the room marks the talent's messages read.
     */
    public function show(Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->markThreadRead($deal, 'brand');

        $deal->load(['talent', 'service', 'currentStep', 'steps', 'messages']);

        return response()->success([
            'deal' => new DealResource($deal),
            'steps' => DealStepResource::collection($deal->steps),
            'messages' => DealMessageResource::collection($deal->messages),
            'can_act' => $deal->currentStep?->actionableBy('brand') ?? false,
        ]);
    }

    /**
     * Complete the current step
     *
     * Submits the current step for the brand (submit brief / accept quote / sign /
     * pay). The body shape depends on the step's `step_type`.
     */
    public function advance(Request $request, Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->advance($deal, $request->all(), 'brand', $this->brand());

        return response()->success(null, __('Step completed.'));
    }

    /**
     * Reject the current step
     *
     * @bodyParam reason string A note explaining the rejection. Example: Please revise the quote.
     */
    public function reject(Request $request, Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->reject($deal, 'brand', $request->input('reason'), $this->brand());

        return response()->success(null, __('Sent back for revision.'));
    }

    /**
     * Skip the current step (if skippable)
     */
    public function skip(Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->skip($deal, 'brand', $this->brand());

        return response()->success(null, __('Step skipped.'));
    }

    /**
     * Post a message to the deal thread
     *
     * @bodyParam body string required The message. Example: Looks great — approving now.
     */
    public function message(Request $request, Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $this->deals->postMessage($deal, 'brand', $this->brand(), $data['body']);

        return response()->success(null, __('Message sent.'));
    }
}
