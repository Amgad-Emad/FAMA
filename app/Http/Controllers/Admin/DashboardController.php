<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\Deal;
use App\Models\DealFlow;
use App\Models\Review;
use App\Models\Talent;
use Illuminate\View\View;

/**
 * Admin dashboard home — a governance overview: pending queues, active deals
 * awaiting admin, flow/catalog counts. Visible to any authenticated admin.
 */
class DashboardController extends AdminController
{
    public function index(): View
    {
        $stats = [
            'flows' => DealFlow::count(),
            'active_flows' => DealFlow::where('is_active', true)->count(),
            'pending_reviews' => Review::where('is_approved', false)->where('status', 'pending')->count(),
            'pending_brand_reviews' => BrandReview::where('is_approved', false)->where('status', 'pending')->count(),
            'active_deals' => Deal::whereIn('status', ['awaiting_brand', 'awaiting_talent', 'awaiting_admin'])->count(),
            'awaiting_admin' => Deal::where('status', 'awaiting_admin')->count(),
            'talents' => Talent::count(),
            'brands' => Brand::count(),
        ];

        return view('admin.dashboard', [
            'admin' => $this->admin(),
            'stats' => $stats,
        ]);
    }
}
