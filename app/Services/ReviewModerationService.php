<?php

namespace App\Services;

use App\Models\BrandReview;
use App\Models\Review;
use App\Models\User;
use App\States\BrandReview\Approved as BrandApproved;
use App\States\BrandReview\Rejected as BrandRejected;
use App\States\Review\Approved;
use App\States\Review\Rejected;

/**
 * Admin review-queue moderation (Phase 3A) for talent reviews AND brand reviews,
 * with batch approve/reject. Gated on `moderate-content`; transactional +
 * activity-logged (per review) with the admin as causer.
 */
class ReviewModerationService extends AdminService
{
    // --- Talent reviews -----------------------------------------------------

    public function approve(User $admin, Review $review): Review
    {
        $this->authorizeAdmin($admin, 'moderate', $review);

        return $this->runInTransaction(function () use ($admin, $review): Review {
            if ($review->status->canTransitionTo(Approved::class)) {
                $review->status->transitionTo(Approved::class);
            }
            $this->record($admin, $review, 'moderation', 'review.approved');

            return $review->refresh();
        }, ['review_id' => $review->getKey()]);
    }

    public function reject(User $admin, Review $review): Review
    {
        $this->authorizeAdmin($admin, 'moderate', $review);

        return $this->runInTransaction(function () use ($admin, $review): Review {
            if ($review->status->canTransitionTo(Rejected::class)) {
                $review->status->transitionTo(Rejected::class);
            }
            $this->record($admin, $review, 'moderation', 'review.rejected');

            return $review->refresh();
        }, ['review_id' => $review->getKey()]);
    }

    /**
     * @param  list<int>  $ids
     * @return int number moderated
     */
    public function approveBatch(User $admin, array $ids): int
    {
        $this->authorizeAdmin($admin, 'moderate', Review::class);

        return $this->runInTransaction(fn () => $this->moderateBatch($admin, $ids, Approved::class, 'review.approved'), ['ids' => $ids]);
    }

    /**
     * @param  list<int>  $ids
     * @return int number moderated
     */
    public function rejectBatch(User $admin, array $ids): int
    {
        $this->authorizeAdmin($admin, 'moderate', Review::class);

        return $this->runInTransaction(fn () => $this->moderateBatch($admin, $ids, Rejected::class, 'review.rejected'), ['ids' => $ids]);
    }

    /**
     * @param  list<int>  $ids
     * @param  class-string  $state
     */
    private function moderateBatch(User $admin, array $ids, string $state, string $description): int
    {
        $count = 0;
        foreach (Review::query()->whereKey($ids)->get() as $review) {
            if ($review->status->canTransitionTo($state)) {
                $review->status->transitionTo($state);
                $this->record($admin, $review, 'moderation', $description, ['batch' => true]);
                $count++;
            }
        }

        return $count;
    }

    // --- Brand reviews ------------------------------------------------------

    public function approveBrandReview(User $admin, BrandReview $review): BrandReview
    {
        $this->authorizeAdmin($admin, 'moderate', $review);

        return $this->runInTransaction(function () use ($admin, $review): BrandReview {
            if ($review->status->canTransitionTo(BrandApproved::class)) {
                $review->status->transitionTo(BrandApproved::class);
            }
            $this->record($admin, $review, 'moderation', 'brand_review.approved');

            return $review->refresh();
        }, ['brand_review_id' => $review->getKey()]);
    }

    public function rejectBrandReview(User $admin, BrandReview $review): BrandReview
    {
        $this->authorizeAdmin($admin, 'moderate', $review);

        return $this->runInTransaction(function () use ($admin, $review): BrandReview {
            if ($review->status->canTransitionTo(BrandRejected::class)) {
                $review->status->transitionTo(BrandRejected::class);
            }
            $this->record($admin, $review, 'moderation', 'brand_review.rejected');

            return $review->refresh();
        }, ['brand_review_id' => $review->getKey()]);
    }
}
