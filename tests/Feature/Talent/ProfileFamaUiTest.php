<?php

use App\Actions\Talent\SyncGoLiveState;
use App\Events\TalentProfileViewed;
use App\Models\BlockType;
use App\Models\Equipment;
use App\Models\Project;
use App\Models\Review;
use App\Models\Speciality;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\ProfileCompletionService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Support\Facades\Event;

/*
 * The profile area on the fama-ui design (2026-10-12): the owner's Preview of a
 * private profile, the folded-in completion steps, the reviews flag on the
 * public page, and the content editor's prefill values.
 */

beforeEach(function () {
    $this->withoutVite();
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
});

/** A talent with one universal block of the given type. */
function fuiTalentWithBlock(array $attributes, string $key): Talent
{
    $talent = Talent::factory()->create($attributes);
    $talent->profileBlocks()->create([
        'block_type_id' => BlockType::where('key', $key)->firstOrFail()->id,
        'talent_type_id' => null, 'position' => 0, 'is_visible' => true, 'status' => 'visible', 'settings' => [],
    ]);

    return $talent;
}

it('lets the owner preview their own private profile, and 404s it for everyone else', function () {
    $talent = Talent::factory()->create(['slug' => 'quiet-one', 'display_name' => 'Quiet One', 'status' => 'unpublished', 'is_published' => false]);
    $other = Talent::factory()->create(['is_published' => true]);

    $this->get('/quiet-one')->assertNotFound();
    $this->actingAs($other, 'talent')->get('/quiet-one')->assertNotFound();

    $this->actingAs($talent, 'talent')->get('/quiet-one')
        ->assertOk()
        ->assertSee('Quiet One')
        ->assertSee(__('Your profile is Private. This is how people will see it once you go Live.'))
        ->assertSee(__('Edit profile'));
});

it('does not count the owner looking at their own profile as a view', function () {
    Event::fake([TalentProfileViewed::class]);
    $talent = Talent::factory()->create(['is_published' => true]);

    $this->actingAs($talent, 'talent')->get('/'.$talent->slug)->assertOk()
        ->assertSee(__('This is how everyone sees your profile.'));
    Event::assertNotDispatched(TalentProfileViewed::class);

    auth('talent')->logout();
    $this->get('/'.$talent->slug)->assertOk()->assertDontSee(__('This is how everyone sees your profile.'));
    Event::assertDispatched(TalentProfileViewed::class);
});

it('hides the reviews section on the public profile while features.reviews is off', function () {
    $talent = fuiTalentWithBlock(['is_published' => true], 'reviews');
    Review::factory()->for($talent)->create(['rating' => 5, 'body' => 'HiddenWhileFlagOff']);

    $this->get('/'.$talent->slug)->assertOk()->assertDontSee('HiddenWhileFlagOff');

    config(['features.reviews' => true]);
    $this->get('/'.$talent->slug)->assertOk()->assertSee('HiddenWhileFlagOff');
});

it('leaves an empty section out for everyone, the owner included', function () {
    $talent = fuiTalentWithBlock(['is_published' => true], 'brand_collabs');

    $this->get('/'.$talent->slug)->assertOk()->assertDontSee(__('Nothing here yet.'))->assertDontSee('blk-brand_collabs');
    $this->actingAs($talent, 'talent')->get('/'.$talent->slug)->assertOk()
        ->assertDontSee(__('Nothing here yet.'))->assertDontSee('blk-brand_collabs');
});

it('seeds a Projects section into every skill tab', function () {
    $legacy = TalentType::whereIn('slug', ['photography', 'modeling', 'styling', 'makeup'])->get();

    expect($legacy)->not->toBeEmpty();
    $legacy->each(fn ($type) => expect($type->default_blocks)->toContain('projects'));
});

it('reports the Go Live and Stand out steps from real data (ADR-AC)', function () {
    $talent = Talent::factory()->create(['display_name' => 'Nour', 'base_city' => '', 'headline' => ['en' => ''], 'bio' => ['en' => '']]);
    $service = app(ProfileCompletionService::class);

    expect($service->goLiveSteps($talent))->toBe(['name' => true, 'skill' => false, 'place' => false])
        ->and($service->standOutSteps($talent))->toBe([
            'show' => false, 'projects3' => false, 'headline' => false, 'photo' => false,
            'brands' => false, 'links' => false, 'bio' => false,
        ])
        ->and($service->canGoLive($talent))->toBeFalse();

    $type = TalentType::where('slug', 'photography')->firstOrFail();
    $talent->talentTypes()->attach($type->id, ['is_primary' => true, 'position' => 0]);
    $speciality = Speciality::factory()->create(['talent_type_id' => $type->id]);
    $talent->specialities()->attach($speciality->id);
    $talent->update(['base_city' => 'Cairo', 'bio' => ['en' => 'Ten years in fashion.'], 'headline' => ['en' => 'Photographer']]);
    Project::factory()->for($talent)->create(['talent_type_id' => $type->id]);

    $fresh = $talent->fresh();
    expect($service->canGoLive($fresh))->toBeTrue()
        ->and($service->standOutSteps($fresh))->toMatchArray(['show' => true, 'projects3' => false, 'headline' => true, 'bio' => true])
        ->and($service->isProfileDone($fresh))->toBeFalse();
});

it('shows the slim path bar into the onboarding (no verification step) on Profile (ADR-AR)', function () {
    $talent = Talent::factory()->draft()->create(['is_verified' => false]);

    $this->actingAs($talent, 'talent')->get(route('talent.profile.edit'))
        ->assertOk()
        ->assertSee('id="gate"', false)
        ->assertSee(e(__('Your first project')), false)
        // The bar's action leads into the onboarding (the URL rides in the strings payload).
        ->assertSee('onboardingUrl', false)
        ->assertDontSee(__('Checked by Fama'));
});

it('serves the saved profile path as JSON for the editor', function () {
    $talent = Talent::factory()->draft()->goLiveReady()->create();
    app(SyncGoLiveState::class)($talent);

    $this->actingAs($talent, 'talent')->getJson(route('talent.profile.state'))
        ->assertOk()
        ->assertJsonPath('data.can_go_live', true)
        // ADR-AQ: the steps make it Private; Go Live is the talent's call.
        ->assertJsonPath('data.is_published', false)
        ->assertJsonPath('data.stage', 'private')
        ->assertJsonPath('data.listed', false)
        ->assertJsonPath('data.profile_done', false)
        ->assertJsonPath('data.steps.skill', true);
});

it('redirects the old dashboard after login straight on to Profile structure', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->followingRedirects()->get(route('talent.dashboard'))
        ->assertOk()
        ->assertSee(__('Core details'));
});

it('returns every field value with a content item so Edit can prefill', function () {
    $talent = Talent::factory()->create();
    $item = Equipment::create([
        'talent_id' => $talent->id, 'category' => 'camera', 'brand' => 'Sony', 'model' => 'A7 IV',
        'name' => 'Main body', 'notes' => ['en' => 'Two batteries'], 'position' => 0,
    ]);

    $this->actingAs($talent, 'talent')->getJson(route('talent.content.data', ['type' => 'equipment']))
        ->assertOk()
        ->assertJsonPath('data.0.id', $item->id)
        ->assertJsonPath('data.0.values.category', 'camera')
        ->assertJsonPath('data.0.values.brand', 'Sony')
        ->assertJsonPath('data.0.values.notes.en', 'Two batteries');
});

it('lists recognitions and points willing-to-explore back to Profile structure on the content page', function () {
    $talent = fuiTalentWithBlock([], 'recognitions');
    $talent->profileBlocks()->create([
        'block_type_id' => BlockType::where('key', 'interests')->firstOrFail()->id,
        'talent_type_id' => null, 'position' => 1, 'is_visible' => true, 'status' => 'visible', 'settings' => [],
    ]);

    $this->actingAs($talent, 'talent')->get(route('talent.content', ['section' => 'universal']))
        ->assertOk()
        ->assertSee('id="block-recognitions"', false)
        ->assertSee('id="block-interests"', false)
        ->assertSee(route('talent.profile.edit').'#explore', false);
});

it('caps social links at nine per profile (the editor stops at nine too)', function () {
    $talent = Talent::factory()->create();
    $platforms = ['instagram', 'tiktok', 'x', 'linkedin', 'youtube', 'behance', 'vimeo', 'website', 'other'];
    $handles = array_map(fn ($p) => ['platform' => $p, 'handle' => 'me', 'url' => 'https://example.com/'.$p], $platforms);

    $this->actingAs($talent, 'talent')->putJson(route('talent.profile.social'), ['handles' => $handles])->assertOk();
    expect($talent->socialHandles()->count())->toBe(9);

    $handles[] = ['platform' => 'website', 'handle' => 'ten', 'url' => 'https://example.com/ten'];
    $this->actingAs($talent, 'talent')->putJson(route('talent.profile.social'), ['handles' => $handles])
        ->assertStatus(422)
        ->assertJsonValidationErrors('handles');
    expect($talent->socialHandles()->count())->toBe(9);
});

it('shows the profile link LOCKED on Profile structure while Private (prototype 9 Oct 2026, ADR-AH)', function () {
    $private = Talent::factory()->create(['slug' => 'hush-hush', 'is_published' => false]);

    $this->actingAs($private, 'talent')->get(route('talent.profile.edit'))->assertOk()
        ->assertSee('x-text="linkLabel"', false)              // the link is there…
        ->assertSee('off: !isPublished', false)                // …dashed while Private…
        ->assertSee("'#i-lock'", false)                        // …with a lock…
        ->assertSee(__('Locked'))                              // …reading "Locked"
        ->assertSee(__('Finish Go Live first. Then you can share your link.'));
});

it('greys out Share on the owner preview of a private profile, and enables it once Live', function () {
    $talent = Talent::factory()->create(['is_published' => false]);

    $this->actingAs($talent, 'talent')->get('/'.$talent->slug)->assertOk()
        ->assertSee(__('Share profile'))
        ->assertSee('aria-disabled="true"', false)
        ->assertSee(__('Your link can be shared once your profile is Live'));

    $talent->update(['is_published' => true]);
    $this->actingAs($talent, 'talent')->get('/'.$talent->slug)->assertOk()
        ->assertSee(__('Share profile'))
        ->assertDontSee(__('Your link can be shared once your profile is Live'));
});

it('opens the public profile on the first skill by position (no separate primary)', function () {
    $talent = Talent::factory()->create(['slug' => 'ordered', 'is_published' => true]);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $photo = TalentType::where('slug', 'photography')->firstOrFail();
    // A legacy row whose flag disagrees with the order: the ORDER wins.
    $talent->talentTypes()->attach([
        $photo->id => ['is_primary' => false, 'position' => 0],
        $model->id => ['is_primary' => true, 'position' => 1],
    ]);
    $gallery = BlockType::where('key', 'gallery')->firstOrFail()->id;
    foreach ([$photo->id, $model->id] as $scope) {
        $talent->profileBlocks()->create(['block_type_id' => $gallery, 'talent_type_id' => $scope, 'position' => 0, 'is_visible' => true, 'status' => 'visible', 'settings' => []]);
    }

    $html = $this->get('/ordered')->assertOk()->getContent();

    expect($html)->toContain("active: 'photography'")
        ->and(strpos($html, 'id="skill-tab-photography"'))->toBeLessThan(strpos($html, 'id="skill-tab-modeling"'))
        ->and($html)->not->toContain(__('Primary'));
});
