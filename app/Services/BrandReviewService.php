<?php

namespace App\Services;

use App\Models\BrandReview;
use App\Models\Contract;
use App\States\BrandReview\Approved;
use App\States\BrandReview\Rejected;
use InvalidArgumentException;

/**
 * Brand-review flow (brand-spec workflow 9). On contract completion the talent may
 * rate the brand on three axes → the review lands pending (is_approved=false),
 * becomes visible only after admin approval, and is never editable by the brand.
 * Transactional, fail-logged to the `brands` channel.
 */
class BrandReviewService extends Service
{
    protected string $logChannel = 'brands';

    /**
     * The talent submits a review for a completed contract.
     *
     * @param  array{communication_rating: int, fairness_rating: int, creative_respect_rating: int, body?: string}  $data
     */
    public function submit(Contract $contract, array $data): BrandReview
    {
        return $this->runInTransaction(function () use ($contract, $data): BrandReview {
            if ($contract->status->getValue() !== 'completed') {
                throw new InvalidArgumentException('A brand can only be reviewed once the contract is completed.');
            }

            if ($contract->brandReview()->exists()) {
                throw new InvalidArgumentException('This contract has already been reviewed.');
            }

            return BrandReview::create([
                'brand_id' => $contract->brand_id,
                'talent_id' => $contract->talent_id,
                'contract_id' => $contract->getKey(),
                'communication_rating' => $data['communication_rating'],
                'fairness_rating' => $data['fairness_rating'],
                'creative_respect_rating' => $data['creative_respect_rating'],
                'body' => $data['body'] ?? null,
                'is_approved' => false,
                'status' => 'pending',
            ]);
        }, ['contract_id' => $contract->getKey()]);
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
