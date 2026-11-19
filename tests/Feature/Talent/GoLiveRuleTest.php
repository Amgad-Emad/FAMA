<?php

use App\Actions\Talent\SyncGoLiveState;
use App\Models\BrandCollab;
use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\Showreel;
use App\Models\Speciality;
use App\Models\Talent;
use App\Models\TalentSocialHandle;
use App\Models\TalentType;
use App\Models\User;
use App\Services\ProfileCompletionService;
use App\Services\SkillsService;
use App\Services\TalentProfileService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;

/**
 * ADR-AC (2026-11-04, per the co-founder prototype, stakeholder-confirmed):
 * Go Live = display name · a skill AND a speciality · city; no review step.
 * Stand out is advisory; its first step lists a Live profile in the Creatives
 * Index. Profile done = every Go Live and Stand out step.
 */

/** A talent with every Go Live AND Stand out step done. */
function doneTalent(): Talent
{
    $talent = Talent::factory()->draft()->goLiveReady()->create([
        'headline' => ['en' => 'Art director & stylist'], 'bio' => ['en' => 'Stylist based in Cairo.'],
    ]);
    $talent->addMedia(UploadedFile::fake()->image('me.jpg'))->toMediaCollection('avatar');
    Project::factory()->count(3)->for($talent)->create();
    BrandCollab::factory()->for($talent)->create();
    TalentSocialHandle::factory()->for($talent)->create();
    app(SyncGoLiveState::class)($talent);

    return $talent->fresh();
}

it('takes a profile Live by itself once the three Go Live steps are saved', function () {
    $talent = Talent::factory()->draft()->unverified()->create(['display_name' => 'Nour', 'base_city' => 'Cairo']);
    $type = TalentType::factory()->create();
    $speciality = Speciality::factory()->create(['talent_type_id' => $type->id]);
    app(SkillsService::class)->addType($talent, $type, true);

    expect($talent->fresh()->is_published)->toBeFalse();

    app(SkillsService::class)->setSpecialities($talent->fresh(), $type, [$speciality->id]);

    expect($talent->fresh()->status->getValue())->toBe('live')
        ->and($talent->fresh()->is_published)->toBeTrue()
        ->and($talent->fresh()->is_verified)->toBeFalse();
});

it('needs the city, not the country, to go Live', function () {
    $talent = Talent::factory()->draft()->goLiveReady()->create(['base_city' => 'Cairo', 'base_country' => null]);

    expect(app(ProfileCompletionService::class)->canGoLive($talent))->toBeTrue();
});

it('takes a Live profile back to Draft when a save empties a Go Live step', function () {
    $talent = Talent::factory()->draft()->goLiveReady()->create(['base_city' => 'Cairo']);
    app(SyncGoLiveState::class)($talent);
    expect($talent->fresh()->is_published)->toBeTrue();

    $this->actingAs($talent->fresh(), 'talent')->patchJson('/talent/profile', ['base_city' => ''])->assertOk();

    expect($talent->fresh()->status->getValue())->toBe('draft')
        ->and($talent->fresh()->is_published)->toBeFalse();
});

it('grandfathers a profile that was Live before the rule (no speciality) — an unrelated save never pulls it down', function () {
    $talent = Talent::factory()->create(['display_name' => 'Legacy']); // live, no speciality

    $this->actingAs($talent, 'talent')->patchJson('/talent/profile', ['bio' => ['en' => 'Hello.']])->assertOk();

    expect($talent->fresh()->is_published)->toBeTrue();
});

it('never undoes a deliberate hold: Unpublished and Suspended stay down on save', function (string $status) {
    $talent = Talent::factory()->goLiveReady()->create(['status' => $status, 'is_published' => false]);

    app(TalentProfileService::class)->updateCore($talent, ['bio' => ['en' => 'Hello.']]);

    expect($talent->fresh()->status->getValue())->toBe($status)
        ->and($talent->fresh()->is_published)->toBeFalse();
})->with(['unpublished', 'suspended']);

it('lists a Live profile in the Creatives Index only once it has something to show', function () {
    $service = app(ProfileCompletionService::class);
    $talent = Talent::factory()->draft()->goLiveReady()->create();
    app(SyncGoLiveState::class)($talent);
    $talent = $talent->fresh();

    expect($service->isListed($talent))->toBeFalse();

    PortfolioItem::factory()->count(2)->for($talent)->create(['media_type' => 'image']);
    expect($service->standOutSteps($talent)['show'])->toBeFalse(); // two photos are not three

    Showreel::factory()->for($talent)->create();
    expect($service->isListed($talent))->toBeTrue();
});

it('counts every Stand out step, and calls the profile done only when all ten are', function () {
    $talent = doneTalent();
    $service = app(ProfileCompletionService::class);

    expect($service->steps($talent))->each->toBeTrue()
        ->and($service->isProfileDone($talent))->toBeTrue()
        ->and($service->homeRoute($talent))->toBe('talent.projects');

    $talent->setTranslation('headline', 'en', '')->save();
    expect($service->isProfileDone($talent->fresh()))->toBeFalse()
        ->and($service->homeRoute($talent->fresh()))->toBe('talent.profile.edit');
});

it('lets an admin Publish a talent regardless of verification', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $talent = Talent::factory()->create(['status' => 'unpublished', 'is_published' => false, 'is_verified' => false]);

    $this->actingAs($admin, 'admin')->patchJson("/admin/moderation/talents/{$talent->id}/publish")->assertOk();

    expect($talent->fresh()->status->getValue())->toBe('live');
});
