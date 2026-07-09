<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\Deal;
use App\Models\DealFlow;
use App\Models\Review;
use App\Models\Talent;
use Illuminate\Http\JsonResponse;

/**
 * @group Admin (lite)
 *
 * @authenticated
 *
 * Read-only governance overview for an admin mobile client — the same counts the
 * web dashboard shows, so a moderator can see what needs attention on the go.
 * Heavy admin (flow building, moderation actions, deal intervention) stays on the
 * web. Any authenticated admin may read this (route gated by `abilities:admin`).
 */
class OverviewController extends Controller
{
    /**
     * Governance overview
     *
     * Pending queues, deals awaiting admin, and flow/catalog counts.
     */
    public function index(): JsonResponse
    {
        return response()->success([
            'flows' => DealFlow::count(),
            'active_flows' => DealFlow::where('is_active', true)->count(),
            'pending_reviews' => Review::where('is_approved', false)->where('status', 'pending')->count(),
            'pending_brand_reviews' => BrandReview::where('is_approved', false)->where('status', 'pending')->count(),
            'active_deals' => Deal::whereIn('status', ['awaiting_brand', 'awaiting_talent', 'awaiting_admin'])->count(),
            'awaiting_admin' => Deal::where('status', 'awaiting_admin')->count(),
            'talents' => Talent::count(),
            'brands' => Brand::count(),
        ]);
    }
}
