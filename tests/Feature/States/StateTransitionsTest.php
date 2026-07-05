<?php

use App\Models\AgencyAffiliation;
use App\Models\PortfolioItem;
use App\Models\ProfileBlock;
use App\Models\Review;
use App\Models\Service;
use App\Models\Talent;
use App\States;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

// ----- Talent profile --------------------------------------------------------

it('talent profile: draft → live is allowed, live → draft is illegal', function () {
    $talent = Talent::factory()->draft()->create(['display_name' => 'X']);

    $talent->status->transitionTo(States\TalentProfile\Live::class);
    expect($talent->status->getValue())->toBe('live');

    expect(fn () => $talent->status->transitionTo(States\TalentProfile\Draft::class))
        ->toThrow(CouldNotPerformTransition::class);
});

it('talent profile: created → live is illegal (must pass through draft)', function () {
    $talent = Talent::factory()->create(['status' => 'created', 'display_name' => 'X']);

    expect(fn () => $talent->status->transitionTo(States\TalentProfile\Live::class))
        ->toThrow(CouldNotPerformTransition::class);
});

// ----- Availability ----------------------------------------------------------

it('availability cycles available → booked → unavailable', function () {
    $talent = Talent::factory()->create(['availability_status' => 'available']);

    $talent->availability_status->transitionTo(States\Availability\Booked::class);
    expect($talent->availability_status->getValue())->toBe('booked');

    $talent->availability_status->transitionTo(States\Availability\Unavailable::class);
    expect($talent->availability_status->getValue())->toBe('unavailable');
});

// ----- Block -----------------------------------------------------------------

it('block: visible ⇄ hidden; a same-state transition is illegal', function () {
    $block = ProfileBlock::factory()->create(['status' => 'visible']);

    $block->status->transitionTo(States\Block\Hidden::class);
    expect($block->status->getValue())->toBe('hidden');

    expect(fn () => $block->status->transitionTo(States\Block\Hidden::class))
        ->toThrow(CouldNotPerformTransition::class);
});

// ----- Review ----------------------------------------------------------------

it('review: pending → approved; rejected → approved is illegal', function () {
    $review = Review::factory()->pending()->create();
    $review->status->transitionTo(States\Review\Approved::class);
    expect($review->status->getValue())->toBe('approved');

    $rejected = Review::factory()->create(['status' => 'rejected']);
    expect(fn () => $rejected->status->transitionTo(States\Review\Approved::class))
        ->toThrow(CouldNotPerformTransition::class);
});

// ----- Service ---------------------------------------------------------------

it('service: active ⇄ paused', function () {
    $service = Service::factory()->create(['status' => 'active']);

    $service->status->transitionTo(States\ServiceStatus\Paused::class);
    expect($service->status->getValue())->toBe('paused');

    $service->status->transitionTo(States\ServiceStatus\Active::class);
    expect($service->status->getValue())->toBe('active');
});

// ----- Affiliation -----------------------------------------------------------

it('affiliation: current → past; past → current is illegal', function () {
    $affiliation = AgencyAffiliation::factory()->create(['status' => 'current']);

    $affiliation->status->transitionTo(States\Affiliation\Past::class);
    expect($affiliation->status->getValue())->toBe('past');

    expect(fn () => $affiliation->status->transitionTo(States\Affiliation\Current::class))
        ->toThrow(CouldNotPerformTransition::class);
});

// ----- Portfolio media -------------------------------------------------------

it('portfolio media: uploaded → processed → ordered; skipping to visible is illegal', function () {
    $item = PortfolioItem::factory()->create(['status' => 'uploaded']);
    $item->status->transitionTo(States\PortfolioMedia\Processed::class);
    $item->status->transitionTo(States\PortfolioMedia\Ordered::class);
    expect($item->status->getValue())->toBe('ordered');

    $fresh = PortfolioItem::factory()->create(['status' => 'uploaded']);
    expect(fn () => $fresh->status->transitionTo(States\PortfolioMedia\Visible::class))
        ->toThrow(CouldNotPerformTransition::class);
});
