<?php

namespace App\Http\Controllers\Api\V1\Talent;

use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\TalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Talent · Reviews
 *
 * @authenticated
 *
 * The talent's own review moderation — filterable, paginated queue; approve/reject
 * via the Review state machine. (Public submission is under Discovery.)
 */
class ReviewController extends TalentApiController
{
    public function __construct(private readonly TalentProfileService $profile) {}

    /**
     * List my reviews
     *
     * @queryParam status string Filter by pending, approved or rejected. Example: pending
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = $this->talent()->reviews()->latest();
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(15);

        return response()->paginated($paginator, ReviewResource::collection($paginator->getCollection()));
    }

    /**
     * Approve a review
     */
    public function approve(Review $review): JsonResponse
    {
        $this->ensureOwns($review);

        return response()->success(new ReviewResource($this->profile->approveReview($review)), __('Review approved.'));
    }

    /**
     * Reject a review
     */
    public function reject(Review $review): JsonResponse
    {
        $this->ensureOwns($review);

        return response()->success(new ReviewResource($this->profile->rejectReview($review)), __('Review rejected.'));
    }
}
