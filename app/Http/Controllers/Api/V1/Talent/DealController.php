<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Http\Resources\DealMessageResource;
use App\Http\Resources\DealResource;
use App\Http\Resources\DealStepResource;
use App\Models\Deal;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Talent · Deals
 *
 * @authenticated
 *
 * The talent's side of the shared deal engine. The talent acts as the `talent`
 * role; every mutation delegates to DealService and is scoped to a deal the talent
 * is party to (403 otherwise). Step actions (send quote, accept, upload, sign, pay)
 * are all `advance` with the payload shape the current step's `step_type` expects.
 */
class DealController extends TalentApiController
{
    public function __construct(private readonly DealService $deals) {}

    /**
     * List my deals
     *
     * Paginated inbox, newest first, with the counterparty + current step loaded.
     *
     * @queryParam status string Filter by deal status (e.g. awaiting_talent, completed). Example: awaiting_talent
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Deal::forTalent($this->talent()->getKey())
            ->with(['brand', 'service', 'currentStep'])
            ->latest();

        if (in_array($status, ['awaiting_talent', 'awaiting_brand', 'awaiting_admin', 'completed', 'draft', 'cancelled', 'declined', 'expired'], true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(15);

        return response()->paginated($paginator, DealResource::collection($paginator->getCollection()));
    }

    /**
     * Get a deal room
     *
     * The deal, its stepper, the message timeline and whether the talent can act
     * on the current step. Reading the room marks the brand's messages read.
     */
    public function show(Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->markThreadRead($deal, 'talent');

        $deal->load(['brand', 'service', 'currentStep', 'steps', 'messages']);

        return response()->success([
            'deal' => new DealResource($deal),
            'steps' => DealStepResource::collection($deal->steps),
            'messages' => DealMessageResource::collection($deal->messages),
            'can_act' => $deal->currentStep?->actionableBy('talent') ?? false,
        ]);
    }

    /**
     * Complete the current step
     *
     * Submits the current step for the talent (send quote / accept / upload / sign
     * / pay). The body shape depends on the step's `step_type`.
     */
    public function advance(Request $request, Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->advance($deal, $request->all(), 'talent', $this->talent());

        return response()->success(null, __('Step completed.'));
    }

    /**
     * Reject the current step
     *
     * @bodyParam reason string A note explaining the rejection. Example: Please revise the scope.
     */
    public function reject(Request $request, Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->reject($deal, 'talent', $request->input('reason'), $this->talent());

        return response()->success(null, __('Sent back for revision.'));
    }

    /**
     * Skip the current step (if skippable)
     */
    public function skip(Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $this->deals->skip($deal, 'talent', $this->talent());

        return response()->success(null, __('Step skipped.'));
    }

    /**
     * Post a message to the deal thread
     *
     * @bodyParam body string required The message. Example: Thanks — sending the files now.
     */
    public function message(Request $request, Deal $deal): JsonResponse
    {
        $this->ensureOwns($deal);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $this->deals->postMessage($deal, 'talent', $this->talent(), $data['body']);

        return response()->success(null, __('Message sent.'));
    }
}
