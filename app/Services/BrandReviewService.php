<?php

namespace App\Services;

use App\Models\BrandReview;
use App\Models\Deal;
use App\States\BrandReview\Approved;
use App\States\BrandReview\Rejected;
use InvalidArgumentException;

/**
 * Brand-review flow (brand-spec workflow 9). On deal completion the talent may
 * rate the brand on three axes → the review lands pending (is_approved=false),
 * becomes visible only after admin approval, and is never editable by the brand.
 * Transactional, fail-logged to the `brands` channel.
 */
class BrandReviewService extends Service
{
    protected string $logChannel = 'brands';

    /**
     * The talent submits a review for a completed deal.
     *
     * @param  array{communication_rating: int, fairness_rating: int, creative_respect_rating: int, body?: string}  $data
     */
    public function submit(Deal $deal, array $data): BrandReview
    {
        return $this->runInTransaction(function () use ($deal, $data): BrandReview {
            if ($deal->status->getValue() !== 'completed') {
                throw new InvalidArgumentException('A brand can only be reviewed once the deal is completed.');
            }

            if ($deal->brandReview()->exists()) {
                throw new InvalidArgumentException('This deal has already been reviewed.');
            }

            return BrandReview::create([
                'brand_id' => $deal->brand_id,
                'talent_id' => $deal->talent_id,
                'deal_id' => $deal->getKey(),
                'communication_rating' => $data['communication_rating'],
                'fairness_rating' => $data['fairness_rating'],
                'creative_respect_rating' => $data['creative_respect_rating'],
                'body' => $data['body'] ?? null,
                'is_approved' => false,
                'status' => 'pending',
            ]);
        }, ['deal_id' => $deal->getKey()]);
    }

    public function approve(BrandReview $review): BrandReview
    {
        return $this->runInTransaction(function () use ($review): BrandReview {
            $review->status->transitionTo(Approved::class);

            return $review->refresh();
        }, ['brand_review_id' => $review->getKey()]);
    }

    public function reject(BrandReview $review): BrandReview
    {
        return $this->runInTransaction(function () use ($review): BrandReview {
            $review->status->transitionTo(Rejected::class);

            return $review->refresh();
        }, ['brand_review_id' => $review->getKey()]);
    }
}
