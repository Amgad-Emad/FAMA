<?php

namespace App\Http\Controllers\Brand;

use App\Models\Contract;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Brand dashboard home (brand-spec) — completion status, active contracts + whose
 * turn, recent projects, and the discovery-feed entry point. Brands that have
 * not finished onboarding are redirected into the wizard.
 */
class DashboardController extends BrandController
{
    public function index(): View|RedirectResponse
    {
        $brand = $this->brand()->loadMissing('credibility');

        if (! $brand->is_complete) {
            return redirect()->route('brand.onboarding');
        }

        $activeContracts = Contract::query()
            ->where('brand_id', $brand->getKey())
            ->whereIn('status', ['draft', 'awaiting_brand', 'awaiting_talent', 'awaiting_admin'])
            ->with(['talent', 'currentStep'])
            ->latest()
            ->limit(5)
            ->get();

        $recentProjects = $brand->projects()->latest()->limit(4)->get();

        $stats = [
            'is_published' => (bool) $brand->is_published,
            'is_verified' => (bool) $brand->is_verified,
            'status' => $brand->status->getValue(),
            'completed_projects' => (int) ($brand->credibility?->completed_projects_count ?? 0),
            'active_contracts' => $activeContracts->count(),
            'projects' => $brand->projects()->count(),
        ];

        return view('brand.dashboard', [
            'brand' => $brand,
            'activeContracts' => $activeContracts,
            'recentProjects' => $recentProjects,
            'stats' => $stats,
        ]);
    }
}
