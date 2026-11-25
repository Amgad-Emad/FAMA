<?php

use App\Actions\Talent\SyncGoLiveState;
use App\Models\BrandCollab;
use App\Models\Like;
use App\Models\Post;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentSocialHandle;
use Illuminate\Http\UploadedFile;

/**
 * Landing rule (ADR-AC → ADR-AE → ADR-AR): a talent who signs in lands on
 * Projects once their profile is done (every Go Live and Stand out step); on
 * the guided onboarding while an ESSENTIAL step (Go Live + something to show)
 * is missing; else on Profile. Registration lands on the onboarding; the site
 * root follows the same rule; the visitor's pending feed action is still
 * replayed on login.
 */
function landingDoneTalent(): Talent
{
    $talent = Talent::factory()->draft()->goLiveReady()->create([
        'password' => bcrypt('password'),
        'headline' => ['en' => 'Stylist'], 'bio' => ['en' => 'Based in Cairo.'],
    ]);
    $talent->addMedia(UploadedFile::fake()->image('me.jpg'))->toMediaCollection('avatar');
    Project::factory()->count(3)->for($talent)->create();
    BrandCollab::factory()->for($talent)->create();
    TalentSocialHandle::factory()->for($talent)->create();
    app(SyncGoLiveState::class)($talent);

    return $talent->fresh();
}

it('lands a talent with a done profile on Projects (portfolio first, 13 Nov 2026)', function () {
    $talent = landingDoneTalent();

    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent'])
        ->assertRedirect(route('talent.projects'));
});

it('lands a talent missing an essential step on the guided onboarding (ADR-AR)', function () {
    $talent = Talent::factory()->create(['password' => bcrypt('password')]);

    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent'])
        ->assertRedirect(route('talent.onboarding'));
});

it('lands a talent with the essentials but not every step on Profile (ADR-AR)', function () {
    $talent = Talent::factory()->draft()->goLiveReady()->create(['password' => bcrypt('password')]);
    Project::factory()->for($talent)->create();

    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent'])
        ->assertRedirect(route('talent.profile.edit'));
});

it('still replays the visitor intent on login, whatever the landing', function () {
    $talent = landingDoneTalent();
    $post = Post::factory()->create();

    $this->postJson("/posts/{$post->id}/like")->assertStatus(401);
    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent'])
        ->assertRedirect(route('talent.projects'));

    expect(Like::query()->where([
        'likeable_type' => 'post', 'likeable_id' => $post->id,
        'liker_type' => 'talent', 'liker_id' => $talent->id,
    ])->exists())->toBeTrue();
});

it('sends a signed-in talent from the site root by the same rule', function () {
    $this->actingAs(landingDoneTalent(), 'talent')->get('/')->assertRedirect(route('talent.projects'));
    $this->actingAs(Talent::factory()->create(), 'talent')->get('/')->assertRedirect(route('talent.onboarding'));
});
