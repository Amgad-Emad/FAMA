<?php

use App\Models\Project;
use App\Models\Talent;
use App\Services\ProfileCompletionService;
use Illuminate\Http\UploadedFile;

/**
 * The guided onboarding (ADR-AR): /talent/welcome/{step?} renders the five
 * screens over the existing endpoints, resumes on the right screen, links the
 * project editor both ways, and is the talent's home until the essentials
 * (Go Live + something to show) are saved.
 */
it('opens a brand-new talent on the first screen', function () {
    $talent = Talent::factory()->create(['display_name' => null, 'base_city' => null]);

    $this->actingAs($talent, 'talent')->get(route('talent.onboarding'))
        ->assertOk()
        ->assertSee('famaOnboarding', false)
        ->assertSee(e(__('Let’s start with you')), false)
        ->assertSee(e(__('Show your work')), false)
        ->assertSee(e(__('Live preview of your profile')), false)
        ->assertSee('toggleNow(\'is_available\')', false)
        ->assertSee('x-model="core.worldview"', false)
        ->assertSee('obp-frame', false)
        ->assertSee('step: \'you\'', false);
});

it('resumes on the work screen once Go Live is done but there is nothing to show', function () {
    $talent = Talent::factory()->draft()->goLiveReady()->create();

    expect(app(ProfileCompletionService::class)->onboardingStep($talent))->toBe('work');
    $this->actingAs($talent, 'talent')->get(route('talent.onboarding'))
        ->assertOk()->assertSee('step: \'work\'', false);
});

it('resumes on the first screen with anything left once the essentials are saved', function () {
    $talent = Talent::factory()->draft()->goLiveReady()->create();
    Project::factory()->for($talent)->create();

    $service = app(ProfileCompletionService::class);
    expect($service->needsOnboarding($talent))->toBeFalse()
        ->and($service->onboardingStep($talent))->toBe('you'); // no photo yet

    $talent->addMedia(UploadedFile::fake()->image('me.jpg'))->toMediaCollection('avatar');
    expect($service->onboardingStep($talent->fresh()))->toBe('story'); // story comes before work (2026-11-25)
});

it('opens a named screen, and sends an unknown one to the resume screen', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->get(route('talent.onboarding', ['step' => 'story']))
        ->assertOk()->assertSee('step: \'story\'', false);
    $this->actingAs($talent, 'talent')->get(route('talent.onboarding').'/nope')
        ->assertRedirect(route('talent.onboarding'));
});

it('lists the talent’s projects with an edit link that returns to the onboarding', function () {
    $talent = Talent::factory()->draft()->goLiveReady()->create();
    $project = Project::factory()->for($talent)->create(['title' => ['en' => 'Desert light']]);

    $this->actingAs($talent, 'talent')->get(route('talent.onboarding', ['step' => 'work']))
        ->assertOk()
        ->assertSee('Desert light')
        ->assertSee('edit?from=welcome', false);

    expect(route('talent.projects.edit', ['project' => $project, 'from' => 'welcome']))->toEndWith("/{$project->id}/edit?from=welcome");
});

it('gives the project editor a way back to the work screen when opened from onboarding', function () {
    $talent = Talent::factory()->draft()->goLiveReady()->create();

    $this->actingAs($talent, 'talent')->get(route('talent.projects.new', ['from' => 'welcome']))
        ->assertOk()
        ->assertSee(route('talent.onboarding', ['step' => 'work']), false)
        ->assertSee(__('Back to setup'));

    $this->actingAs($talent, 'talent')->get(route('talent.projects.new'))
        ->assertOk()
        ->assertDontSee(__('Back to setup'));
});

it('reports the resume screen with the saved profile path', function () {
    $talent = Talent::factory()->draft()->goLiveReady()->create();

    $this->actingAs($talent, 'talent')->getJson(route('talent.profile.state'))
        ->assertOk()
        ->assertJsonPath('data.onboarding_step', 'work');
});

it('keeps guests out', function () {
    $this->get(route('talent.onboarding'))->assertRedirect();
    $this->assertGuest('talent');
});

it('frames the real public profile in the live preview (embed mode, owner only)', function () {
    $talent = Talent::factory()->draft()->goLiveReady()->create();

    $this->actingAs($talent, 'talent')->get(route('talent.onboarding'))
        ->assertOk()
        ->assertSee(url($talent->slug).'?embed=1', false);

    // The owner's embed: the page itself, without the side bar / owner strip, with the draft hooks.
    $this->actingAs($talent, 'talent')->get(url($talent->slug).'?embed=1')
        ->assertOk()
        ->assertSee('data-pv="name"', false)
        // hooks the onboarding writes into live: availability, the dot, travel, point of view, links
        ->assertSee('data-pv="avail"', false)
        ->assertSee('data-pv="dot"', false)
        ->assertSee('data-pv="travel"', false)
        ->assertSee('data-pv="pov"', false)
        ->assertSee('data-pv="links"', false)
        ->assertSee('profile-embed.js', false)
        ->assertDontSee('class="fx-nav', false)
        ->assertDontSee(__('This is how everyone sees your profile.'));
});

it('ignores ?embed for anyone but the owner', function () {
    $talent = Talent::factory()->create(['is_published' => true, 'status' => 'live']);

    $this->get(url($talent->slug).'?embed=1')
        ->assertOk()
        ->assertDontSee('profile-embed.js', false);
});
