<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use App\Models\BrandProject;
use App\Models\BrandReview;
use App\Models\Contract;
use App\Models\Review;
use App\Models\Talent;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/**
 * Admin dashboard home — the reachable landing: every admin surface has a
 * permission-gated card here (queue counts, contract whose-turn, project
 * statuses, governance quick links, recent activity). Open to any
 * authenticated admin; each section is computed ONLY when the admin holds its
 * permission, with single aggregate queries (no per-row work).
 */
class DashboardController extends AdminController
{
    public function index(): View
    {
        $admin = $this->admin();
        $stats = [];

        if ($admin->can('moderate-content')) {
            $stats['moderation'] = [
                // Draft/created = profiles built but not yet live — the queue
                // a moderator reviews before publication.
                'pending_talents' => Talent::whereIn('status', ['created', 'draft'])->count(),
                'pending_reviews' => Review::where('status', 'pending')->count(),
                'pending_brand_reviews' => BrandReview::where('status', 'pending')->count(),
                'unverified_brands' => Brand::where('is_verified', false)->count(),
            ];

            $byStatus = BrandProject::toBase()
                ->groupBy('status')->selectRaw('status, count(*) as n')->pluck('n', 'status');
            $stats['projects'] = [
                'open' => (int) ($byStatus['open'] ?? 0),
                'in_progress' => (int) ($byStatus['in_progress'] ?? 0),
                'completed' => (int) ($byStatus['completed'] ?? 0),
            ];
        }

        if ($admin->can('intervene-contracts')) {
            $turns = Contract::toBase()
                ->whereIn('status', ['awaiting_brand', 'awaiting_talent', 'awaiting_admin'])
                ->groupBy('status')->selectRaw('status, count(*) as n')->pluck('n', 'status');
            $stats['contracts'] = [
                'active' => (int) $turns->sum(),
                'awaiting_brand' => (int) ($turns['awaiting_brand'] ?? 0),
                'awaiting_talent' => (int) ($turns['awaiting_talent'] ?? 0),
                'awaiting_admin' => (int) ($turns['awaiting_admin'] ?? 0),
            ];
        }

        if ($admin->can('manage-settings')) {
            $stats['recent_activity'] = Activity::query()->with('causer')->latest('id')->take(6)->get();
        }

        return view('admin.dashboard', [
            'admin' => $admin,
            'stats' => $stats,
        ]);
    }
}
