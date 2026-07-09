<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DealResource;
use App\Models\Brand;
use App\Models\Deal;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Deals
 *
 * The authenticated party's view of the shared deal engine. A talent token sees
 * the deals it is party to; a brand token sees the deals it initiated/received.
 * Read-only here — step actions (advance/reject/message) stay on the web deal
 * room for now and are a later API slice.
 *
 * @authenticated
 */
class DealController extends Controller
{
    /**
     * List my deals
     *
     * Paginated deals scoped to the authenticated talent or brand, newest
     * activity first, with the counterparty, service and current step loaded.
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->scopedQuery($request)
            ->with(['brand', 'talent', 'service', 'currentStep'])
            ->latest('updated_at')
            ->paginate(15);

        return response()->paginated($paginator, DealResource::collection($paginator->getCollection()));
    }

    /**
     * Show a deal
     *
     * A single deal the caller is a party to (403 otherwise).
     */
    public function show(Request $request, Deal $deal): JsonResponse
    {
        abort_unless($this->isParty($request->user(), $deal), 403);

        $deal->load(['brand', 'talent', 'service', 'currentStep', 'steps']);

        return response()->success(new DealResource($deal));
    }

    /**
     * Build the deal query scoped to whichever entity the token belongs to.
     *
     * @return Builder<Deal>
     */
    private function scopedQuery(Request $request): Builder
    {
        $entity = $request->user();

        if ($entity instanceof Talent) {
            return Deal::query()->where('talent_id', $entity->getKey());
        }

        if ($entity instanceof Brand) {
            return Deal::query()->where('brand_id', $entity->getKey());
        }

        // Any other token type (e.g. admin) has no personal deal inbox here.
        abort(403);
    }

    /**
     * Whether the given entity is a party (talent or brand) to the deal.
     */
    private function isParty(mixed $entity, Deal $deal): bool
    {
        return ($entity instanceof Talent && (int) $deal->talent_id === $entity->getKey())
            || ($entity instanceof Brand && (int) $deal->brand_id === $entity->getKey());
    }
}
