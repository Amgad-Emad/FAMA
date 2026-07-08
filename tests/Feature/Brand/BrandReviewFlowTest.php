<?php

use App\Models\BrandReview;
use App\Models\Deal;
use App\Services\BrandReviewService;

$ratings = ['communication_rating' => 5, 'fairness_rating' => 4, 'creative_respect_rating' => 5];

it('lets a talent submit a pending review on a completed deal', function () use ($ratings) {
    $deal = Deal::factory()->create(['status' => 'completed']);

    $review = app(BrandReviewService::class)->submit($deal, $ratings + ['body' => 'Fair and clear.']);

    expect($review->status->getValue())->toBe('pending');
    expect((bool) $review->is_approved)->toBeFalse();
    expect($review->brand_id)->toBe($deal->brand_id);
    expect($review->talent_id)->toBe($deal->talent_id);
    expect($review->average_rating)->toBe(4.7);
});

it('refuses a review until the deal is completed', function () use ($ratings) {
    $deal = Deal::factory()->create(['status' => 'awaiting_talent']);

    expect(fn () => app(BrandReviewService::class)->submit($deal, $ratings))
        ->toThrow(InvalidArgumentException::class);
});

it('refuses a second review for the same deal', function () use ($ratings) {
    $deal = Deal::factory()->create(['status' => 'completed']);
    $service = app(BrandReviewService::class);
    $service->submit($deal, $ratings);

    expect(fn () => $service->submit($deal, $ratings))->toThrow(InvalidArgumentException::class);
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
