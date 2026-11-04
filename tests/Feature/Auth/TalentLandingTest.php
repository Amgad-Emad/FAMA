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
 * ADR-AC landing rule (stakeholder-confirmed, prototype FAMA_HOME): a talent
 * who signs in lands on the Feed once their profile is done (every Go Live and
 * Stand out step), else on Profile structure. Registration still lands on
 * Profile structure; the site root follows the same rule for a signed-in
 * talent; the visitor's pending feed action is still replayed on login.
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

it('lands a talent with a done profile on the Feed', function () {
    $talent = landingDoneTalent();

    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent'])
        ->assertRedirect(route('feed'));
});

it('lands a talent with an unfinished profile on Profile structure', function () {
    $talent = Talent::factory()->create(['password' => bcrypt('password')]);

    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent'])
        ->assertRedirect(route('talent.profile.edit'));
});

it('still replays the visitor intent on login, whatever the landing', function () {
    $talent = landingDoneTalent();
    $post = Post::factory()->create();

    $this->postJson("/posts/{$post->id}/like")->assertStatus(401);
    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent'])
        ->assertRedirect(route('feed'));

    expect(Like::query()->where([
        'likeable_type' => 'post', 'likeable_id' => $post->id,
        'liker_type' => 'talent', 'liker_id' => $talent->id,
    ])->exists())->toBeTrue();
});

it('sends a signed-in talent from the site root by the same rule', function () {
    $this->actingAs(landingDoneTalent(), 'talent')->get('/')->assertRedirect(route('feed'));
    $this->actingAs(Talent::factory()->create(), 'talent')->get('/')->assertRedirect(route('talent.profile.edit'));
});
