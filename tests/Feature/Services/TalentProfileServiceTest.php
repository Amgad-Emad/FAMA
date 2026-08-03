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

it('refuses to publish an unverified profile (awaiting admin approval)', function () {
    $talent = Talent::factory()->draft()->unverified()->create(['display_name' => 'Pending']);

    expect(fn () => app(TalentProfileService::class)->publish($talent))
        ->toThrow(InvalidArgumentException::class);

    expect($talent->fresh()->is_published)->toBeFalse();
    expect($talent->fresh()->status->getValue())->toBe('draft');
});

it('lets a talent publish once an admin verifies it', function () {
    $this->seed(Database\Seeders\RolesAndPermissionsSeeder::class);
    $talent = Talent::factory()->draft()->unverified()->create(['display_name' => 'Ready']);
    $admin = App\Models\User::factory()->create();
    $admin->assignRole('super-admin');

    app(App\Services\TalentModerationService::class)->verify($admin, $talent);
    expect($talent->fresh()->is_verified)->toBeTrue();

    app(TalentProfileService::class)->publish($talent->fresh());
    expect($talent->fresh()->status->getValue())->toBe('live');
    expect($talent->fresh()->is_published)->toBeTrue();
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
