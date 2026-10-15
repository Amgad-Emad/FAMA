<?php

namespace App\Http\Controllers\Talent;

use App\Http\Requests\Talent\ViewStatsRequest;
use App\Services\ProfileViewStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Talent dashboard. The fama-ui design (2026-10-12) folded the dashboard into
 * Profile structure — the completion steps and the views card live there now —
 * so the page itself only redirects. The route name stays (`talent.dashboard`)
 * because login redirects and old links still point at it, and the views
 * series endpoint stays here because the Profile structure views card reads it.
 */
class DashboardController extends TalentController
{
    public function __construct(private readonly ProfileViewStatsService $viewStats) {}

    /**
     * The old dashboard home → Profile structure, where its content now lives.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('talent.profile.edit');
    }

    /**
     * Bucketed view series for the views card chart (Ajax). `range` is a
     * validated preset; 'all' reaches back to the profile's creation date.
     */
    public function viewStats(ViewStatsRequest $request): JsonResponse
    {
        return response()->success($this->viewStats->series($this->talent(), $request->range()));
    }
}
