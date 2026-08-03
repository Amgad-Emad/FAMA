<?php

use App\Models\Review;
use App\Models\Talent;
use App\Services\TalentProfileService;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

it('updates core identity fields', function () {
    $talent = Talent::factory()->create();

    app(TalentProfileService::class)->updateCore($talent, ['display_name' => 'New Name', 'base_city' => 'Alexandria']);

    expect($talent->fresh()->display_name)->toBe('New Name');
    expect($talent->fresh()->base_city)->toBe('Alexandria');
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
