<?php

use App\Models\BrandReview;
use App\Models\Contract;
use App\Services\BrandReviewService;

$ratings = ['communication_rating' => 5, 'fairness_rating' => 4, 'creative_respect_rating' => 5];

it('lets a talent submit a pending review on a completed contract', function () use ($ratings) {
    $contract = Contract::factory()->create(['status' => 'completed']);

    $review = app(BrandReviewService::class)->submit($contract, $ratings + ['body' => 'Fair and clear.']);

    expect($review->status->getValue())->toBe('pending');
    expect((bool) $review->is_approved)->toBeFalse();
    expect($review->brand_id)->toBe($contract->brand_id);
    expect($review->talent_id)->toBe($contract->talent_id);
    expect($review->average_rating)->toBe(4.7);
});

it('refuses a review until the contract is completed', function () use ($ratings) {
    $contract = Contract::factory()->create(['status' => 'awaiting_talent']);

    expect(fn () => app(BrandReviewService::class)->submit($contract, $ratings))
        ->toThrow(InvalidArgumentException::class);
});

it('refuses a second review for the same contract', function () use ($ratings) {
    $contract = Contract::factory()->create(['status' => 'completed']);
    $service = app(BrandReviewService::class);
    $service->submit($contract, $ratings);

    expect(fn () => $service->submit($contract, $ratings))->toThrow(InvalidArgumentException::class);
});

it('approves a review (syncs is_approved) and is not editable by the brand', function () {
    $review = BrandReview::factory()->pending()->create();

    $approved = app(BrandReviewService::class)->approve($review);
    expect($approved->status->getValue())->toBe('approved');
    expect((bool) $approved->is_approved)->toBeTrue();

    // The brand has no path to edit a review.
    expect(method_exists(BrandReviewService::class, 'update'))->toBeFalse();
    expect(method_exists(BrandReviewService::class, 'edit'))->toBeFalse();
});
