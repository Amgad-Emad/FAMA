<?php

namespace App\Http\Controllers\Talent;

use Illuminate\View\View;

/**
 * Talent dashboard home — status overview (talent-spec): draft vs live,
 * view_count, pending-reviews count, and the (Phase 1E) deals slot.
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
            'professions' => (int) $talent->talent_types_count,
        ];

        return view('talent.dashboard', ['talent' => $talent, 'stats' => $stats]);
    }
}
