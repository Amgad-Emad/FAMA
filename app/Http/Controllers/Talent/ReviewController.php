<?php

namespace App\Http\Controllers\Talent;

use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Reviews moderation queue (talent-spec) — filterable, paginated list;
 * approve/reject via the Review state machine.
 */
class ReviewController extends TalentController
{
    public function __construct(private readonly TalentProfileService $profile) {}

    public function index(): View
    {
        return view('talent.reviews');
    }

    public function data(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $rating = (int) $request->query('rating');

        $query = $this->talent()->reviews()->latest();
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }
        // Star-rating filter (the V1 reviews page is a read-only list filtered by rate).
        if ($rating >= 1 && $rating <= 5) {
            $query->where('rating', $rating);
        }

        $paginator = $query->paginate(15);

        return response()->paginated($paginator, ReviewResource::collection($paginator->getCollection()));
    }

    public function approve(Review $review): JsonResponse
    {
        $this->ensureOwns($review);

        return response()->success(new ReviewResource($this->profile->approveReview($review)), __('Review approved.'));
    }

    public function reject(Review $review): JsonResponse
    {
        $this->ensureOwns($review);

        return response()->success(new ReviewResource($this->profile->rejectReview($review)), __('Review rejected.'));
    }
}
