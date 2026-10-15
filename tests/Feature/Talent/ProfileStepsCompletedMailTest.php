<?php

use App\Mail\ProfileStepsCompleted;
use App\Models\Project;
use App\Models\Speciality;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\SpecialitySeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

/**
 * Finishing the six profile steps while Fama's check is pending sends ONE
 * "you're done, we're checking it" email.
 */
beforeEach(function () {
    Mail::fake();
    $this->seed([TalentTypeSeeder::class, SpecialitySeeder::class, BlockTypeSeeder::class]);

    // Five of six steps done; the bio is missing.
    $this->talent = Talent::factory()->unverified()->create([
        'is_published' => false, 'display_name' => 'Nour', 'base_city' => 'Cairo', 'base_country' => 'Egypt',
        'bio' => ['en' => ''],
    ]);
    $skill = TalentType::where('slug', 'modeling')->firstOrFail();
    $this->talent->talentTypes()->attach($skill->id, ['is_primary' => true, 'position' => 0]);
    $this->talent->specialities()->attach(Speciality::where('talent_type_id', $skill->id)->value('id'), ['position' => 0]);
    $this->talent->addMedia(UploadedFile::fake()->image('me.jpg'))->toMediaCollection('avatar');
    Project::factory()->create(['talent_id' => $this->talent->id, 'talent_type_id' => $skill->id]);
});

it('emails once when the last step is finished', function () {
    $this->actingAs($this->talent, 'talent')
        ->patchJson('/talent/profile', ['bio' => ['en' => 'Ten years in fashion.']])->assertOk();

    Mail::assertQueued(ProfileStepsCompleted::class, fn ($m) => $m->hasTo($this->talent->email) && $m->name === 'Nour');
    expect($this->talent->fresh()->steps_completed_at)->not->toBeNull();

    // Saving again (still complete) does not send a second one.
    $this->patchJson('/talent/profile', ['bio' => ['en' => 'Eleven years in fashion.']])->assertOk();
    Mail::assertQueued(ProfileStepsCompleted::class, 1);
});

it('sends nothing while a step is still missing', function () {
    $this->actingAs($this->talent, 'talent')
        ->patchJson('/talent/profile', ['display_name' => 'Nour A.'])->assertOk();

    Mail::assertNothingQueued();
});

it('sends nothing to a profile Fama already checked', function () {
    $this->talent->update(['is_verified' => true]);

    $this->actingAs($this->talent, 'talent')
        ->patchJson('/talent/profile', ['bio' => ['en' => 'Ten years in fashion.']])->assertOk();

    Mail::assertNothingQueued();
});

it('renders the email', function () {
    $html = (new ProfileStepsCompleted('Nour', 'https://fama.test/talent/profile'))->render();

    expect($html)->toContain('Nour')->toContain('Checked by Fama')->toContain('https://fama.test/talent/profile');
});

it('shows "Private" in the nav status bar while a step is missing', function () {
    $this->actingAs($this->talent, 'talent')->get(route('talent.projects'))
        ->assertOk()->assertSee('x-data="{ complete: false, verified: false }"', false);
});

it('shows "under review" in the nav status bar once all steps are done', function () {
    $this->talent->setTranslation('bio', 'en', 'Ten years in fashion.')->save();

    $this->actingAs($this->talent, 'talent')->get(route('talent.projects'))
        ->assertOk()->assertSee('x-data="{ complete: true, verified: false }"', false)
        ->assertSee('Your profile is under review');
});

it('shows "ready to go Live" once Fama has checked a complete profile', function () {
    $this->talent->setTranslation('bio', 'en', 'Ten years in fashion.');
    $this->talent->is_verified = true;
    $this->talent->save();

    $this->actingAs($this->talent, 'talent')->get(route('talent.projects'))
        ->assertOk()->assertSee('x-data="{ complete: true, verified: true }"', false);
});
