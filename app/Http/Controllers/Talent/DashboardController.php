<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\ViewStatsRequest;
use App\Models\Contract;
use App\Services\ProfileViewStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Talent dashboard home — status overview (talent-spec): live/private with an
 * inline publish toggle, the views KPI (lifetime total + week-over-week delta,
 * expandable chart), and the active contracts + whose-turn slot.
 */
class DashboardController extends TalentController
{
    public function __construct(private readonly ProfileViewStatsService $viewStats) {}

    public function index(): View
    {
        $talent = $this->talent();

        $stats = [
            'status' => $talent->status->getValue(),
            'is_published' => (bool) $talent->is_published,
            'is_verified' => (bool) $talent->is_verified,
            'view_count' => (int) $talent->view_count,
            'weekly' => $this->viewStats->weeklyComparison($talent),
        ];

        // Live (non-terminal) contracts for the "whose turn" overview — only when
        // the contracts feature is enabled (V1 ships without contracts).
        $activeContracts = \App\Support\Features::contracts()
            ? Contract::forTalent($talent->getKey())
                ->whereIn('status', ['draft', 'awaiting_brand', 'awaiting_talent', 'awaiting_admin'])
                ->with(['brand', 'currentStep'])
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        return view('talent.dashboard', ['talent' => $talent, 'stats' => $stats, 'activeContracts' => $activeContracts]);
    }

    /**
     * Bucketed view series for the expandable KPI chart (Ajax). `range` is a
     * validated preset; 'all' reaches back to the profile's creation date.
     */
    public function viewStats(ViewStatsRequest $request): JsonResponse
    {
        return response()->success($this->viewStats->series($this->talent(), $request->range()));
    }
}
