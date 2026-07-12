<?php

namespace App\Http\Controllers\Talent;

use App\Models\Deal;
use Illuminate\View\View;

/**
 * Talent dashboard home — status overview (talent-spec): draft vs live,
 * view_count, pending-reviews count, and the active deals + whose-turn slot.
 */
class DashboardController extends TalentController
{
    public function index(): View
    {
        $talent = $this->talent()->loadCount([
            'reviews as pending_reviews_count' => fn ($query) => $query->where('is_approved', false),
            'profileBlocks',
            'talentTypes',
        ]);

        $stats = [
            'status' => $talent->status->getValue(),
            'is_published' => (bool) $talent->is_published,
            'view_count' => (int) $talent->view_count,
            'pending_reviews' => (int) $talent->pending_reviews_count,
            'blocks' => (int) $talent->profile_blocks_count,
            'skills' => (int) $talent->talent_types_count,
        ];

        // Live (non-terminal) deals for the "whose turn" overview.
        $activeDeals = Deal::forTalent($talent->getKey())
            ->whereIn('status', ['draft', 'awaiting_brand', 'awaiting_talent', 'awaiting_admin'])
            ->with(['brand', 'currentStep'])
            ->latest()
            ->limit(5)
            ->get();

        return view('talent.dashboard', ['talent' => $talent, 'stats' => $stats, 'activeDeals' => $activeDeals]);
    }
}
