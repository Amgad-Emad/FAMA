<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * @group Admin (lite)
 *
 * @authenticated
 *
 * Read-only recent audit trail for an admin mobile client (route gated by
 * `abilities:manage-settings`). Paginated + causer eager-loaded (no N+1); the
 * same shape the web activity viewer uses.
 */
class ActivityController extends Controller
{
    /**
     * Recent activity
     *
     * @queryParam log string Filter by log name (e.g. deal_flow, moderation). Example: moderation
     * @queryParam q string Free-text description search. Example: verified
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::query()->with('causer')->latest();

        if ($log = $request->query('log')) {
            $query->where('log_name', $log);
        }

        if ($search = $request->string('q')->trim()->value()) {
            $query->where('description', 'like', "%{$search}%");
        }

        $paginator = $query->paginate(30);

        return response()->paginated($paginator, ActivityResource::collection($paginator->getCollection()));
    }
}
