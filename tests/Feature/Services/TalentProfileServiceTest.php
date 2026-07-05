<?php

use App\Models\Review;
use App\Models\Service;
use App\Models\Talent;
use App\Services\TalentProfileService;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

it('updates core identity fields', function () {
    $talent = Talent::factory()->create();

    app(TalentProfileService::class)->updateCore($talent, ['display_name' => 'New Name', 'base_city' => 'Alexandria']);

    expect($talent->fresh()->display_name)->toBe('New Name');
    expect($talent->fresh()->base_city)->toBe('Alexandria');
});

it('moves availability through its state machine', function () {
    $talent = Talent::factory()->create(['availability_status' => 'available']);

    app(TalentProfileService::class)->setAvailability($talent, 'booked');

    expect($talent->fresh()->availability_status->getValue())->toBe('booked');
});

it('publishes (stamping is_published + published_at) then unpublishes', function () {
    $talent = Talent::factory()->draft()->create(['display_name' => 'Pub']);
    $service = app(TalentProfileService::class);

    $service->publish($talent);
    $talent->refresh();
    expect($talent->status->getValue())->toBe('live');
    expect($talent->is_published)->toBeTrue();
    expect($talent->published_at)->not->toBeNull();

    $service->unpublish($talent);
    expect($talent->fresh()->status->getValue())->toBe('unpublished');
    expect($talent->fresh()->is_published)->toBeFalse();
});

it('refuses to publish a profile without a display name (ToLive guard)', function () {
    $talent = Talent::factory()->draft()->create(['display_name' => null]);

    expect(fn () => app(TalentProfileService::class)->publish($talent))
        ->toThrow(CouldNotPerformTransition::class);
});

it('manages the rate card: add, update, pause, activate, remove', function () {
    $talent = Talent::factory()->create();
    $service = app(TalentProfileService::class);

    $rateCard = $service->addService($talent, ['name' => ['en' => 'Shoot'], 'price' => 1000, 'currency' => 'EGP', 'price_unit' => 'day']);
    expect($rateCard->getTranslation('name', 'en'))->toBe('Shoot');

    $service->updateService($rateCard, ['price' => 1500]);
    expect((float) $rateCard->fresh()->price)->toBe(1500.0);

    $service->pauseService($rateCard);
    expect($rateCard->fresh()->status->getValue())->toBe('paused');
    expect($rateCard->fresh()->is_active)->toBeFalse();

    $service->activateService($rateCard->fresh());
    expect($rateCard->fresh()->status->getValue())->toBe('active');
    expect($rateCard->fresh()->is_active)->toBeTrue();

    $service->removeService($rateCard->fresh());
    expect(Service::find($rateCard->id))->toBeNull();
});

it('moderates reviews (approve + reject) syncing is_approved', function () {
    $talent = Talent::factory()->create();
    $service = app(TalentProfileService::class);

    $approved = Review::factory()->pending()->for($talent)->create();
    $service->approveReview($approved);
    expect($approved->fresh()->status->getValue())->toBe('approved');
    expect($approved->fresh()->is_approved)->toBeTrue();

    $rejected = Review::factory()->pending()->for($talent)->create();
    $service->rejectReview($rejected);
    expect($rejected->fresh()->status->getValue())->toBe('rejected');
    expect($rejected->fresh()->is_approved)->toBeFalse();
});

it('adds and ends affiliations and manages press', function () {
    $talent = Talent::factory()->create();
    $service = app(TalentProfileService::class);

    $affiliation = $service->addAffiliation($talent, ['agency_name' => 'Elite', 'representation_type' => 'exclusive', 'region' => 'MENA']);
    expect($affiliation->status->getValue())->toBe('current');

    $service->endAffiliation($affiliation);
    expect($affiliation->fresh()->status->getValue())->toBe('past');
    expect($affiliation->fresh()->is_current)->toBeFalse();

    $press = $service->addPressFeature($talent, ['publication' => 'Vogue', 'title' => 'Feature']);
    expect($talent->pressFeatures()->count())->toBe(1);

    $service->removePressFeature($press);
    expect($talent->pressFeatures()->count())->toBe(0);
});
