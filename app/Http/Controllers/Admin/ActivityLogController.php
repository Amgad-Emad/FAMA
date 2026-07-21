<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\ActivityResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/**
 * Admin activity-log viewer (Phase 3B UI) — a searchable audit trail
 * (subject / causer / changes). Read-only; `can:manage-settings` gates it.
 */
class ActivityLogController extends AdminController
{
    public function index(): View
    {
        return view('admin.activity.index');
    }

    public function data(Request $request): JsonResponse
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
