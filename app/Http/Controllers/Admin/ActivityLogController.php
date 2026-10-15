<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\ActivityResource;
use App\Support\Features;
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
        $query = Activity::query()->with(['causer', 'subject'])->latest('id');

        // Brands are a dormant feature in V1: their audit entries stay in the
        // table but are not shown while the feature is off.
        if (! Features::brands()) {
            $query->where(fn ($q) => $q->whereNull('subject_type')->orWhereNotIn('subject_type', ['brand', 'brand_review', 'brand_project']))
                ->where('description', 'not like', 'brand.%');
        }

        // Period filter (24h / 7d / 30d; anything else = all time).
        $since = match ($request->query('range')) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => null,
        };
        if ($since) {
            $query->where('created_at', '>=', $since);
        }

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
