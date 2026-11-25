<?php

use App\Mail\ProfileStepsCompleted;
use App\Models\Speciality;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\SpecialitySeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Support\Facades\Mail;

/**
 * ADR-AC: finishing the Go Live steps (display name · skill + speciality ·
 * city) takes a Private profile Live by itself and sends ONE "you're Live"
 * e-mail. No review step: verification plays no part.
 */
beforeEach(function () {
    Mail::fake();
    $this->seed([TalentTypeSeeder::class, SpecialitySeeder::class, BlockTypeSeeder::class]);

    // Two of three Go Live steps done; the city is missing.
    $this->talent = Talent::factory()->draft()->unverified()->create([
        'display_name' => 'Nour', 'base_city' => null, 'base_country' => 'Egypt',
    ]);
    $skill = TalentType::where('slug', 'modeling')->firstOrFail();
    $this->talent->talentTypes()->attach($skill->id, ['is_primary' => true, 'position' => 0]);
    $this->talent->specialities()->attach(Speciality::where('talent_type_id', $skill->id)->value('id'), ['position' => 0]);
});

it('turns Private (no e-mail) when the last Go Live step is saved, then emails once on Go Live', function () {
    $this->actingAs($this->talent, 'talent')
        ->patchJson('/talent/profile', ['base_city' => 'Cairo'])->assertOk();

    // ADR-AQ: the steps make the profile Private — the link doesn't work yet, so no "you're Live" mail.
    $fresh = $this->talent->fresh();
    expect($fresh->is_published)->toBeFalse()
        ->and($fresh->status->getValue())->toBe('unpublished')
        ->and($fresh->steps_completed_at)->toBeNull();
    Mail::assertNothingQueued();

    $this->patchJson(route('talent.profile.publish'), ['publish' => true])->assertOk();

    $fresh = $this->talent->fresh();
    expect($fresh->is_published)->toBeTrue()
        ->and($fresh->status->getValue())->toBe('live')
        ->and($fresh->is_verified)->toBeFalse()
        ->and($fresh->steps_completed_at)->not->toBeNull();
    Mail::assertQueued(ProfileStepsCompleted::class, fn ($m) => $m->hasTo($this->talent->email) && $m->name === 'Nour');

    // Saving again, or going Private and Live again, does not send a second one.
    $this->patchJson('/talent/profile', ['bio' => ['en' => 'Ten years in fashion.']])->assertOk();
    $this->patchJson(route('talent.profile.publish'), ['publish' => false])->assertOk();
    $this->patchJson(route('talent.profile.publish'), ['publish' => true])->assertOk();
    Mail::assertQueued(ProfileStepsCompleted::class, 1);
});

it('sends nothing (and stays Private) while a Go Live step is missing', function () {
    $this->actingAs($this->talent, 'talent')
        ->patchJson('/talent/profile', ['display_name' => 'Nour A.'])->assertOk();

    expect($this->talent->fresh()->is_published)->toBeFalse();
    Mail::assertNothingQueued();
});

it('sends nothing to a profile held private (Unpublished) until it actually goes Live', function () {
    $this->talent->forceFill(['status' => 'unpublished'])->save();

    $this->actingAs($this->talent, 'talent')
        ->patchJson('/talent/profile', ['base_city' => 'Cairo'])->assertOk();

    expect($this->talent->fresh()->is_published)->toBeFalse();
    Mail::assertNothingQueued();
});

it('renders the email without any review step', function () {
    $html = (new ProfileStepsCompleted('Nour', 'https://fama.test/talent/profile'))->render();

    expect($html)->toContain('Nour')->toContain('https://fama.test/talent/profile')
        ->toContain(e(__('Your profile is Live: anyone with your link can open it, and you can share it.')))
        ->not->toContain('Checked by Fama');
});
