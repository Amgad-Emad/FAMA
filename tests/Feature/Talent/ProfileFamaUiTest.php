<?php

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
    $talent = Talent::factory()->create(['slug' => 'quiet-one', 'display_name' => 'Quiet One', 'is_published' => false]);
    $other = Talent::factory()->create(['is_published' => true]);

    $this->get('/quiet-one')->assertNotFound();
    $this->actingAs($other, 'talent')->get('/quiet-one')->assertNotFound();

    $this->actingAs($talent, 'talent')->get('/quiet-one')
        ->assertOk()
        ->assertSee('Quiet One')
        ->assertSee(__("Your profile is private. This is how people will see it once it's public."))
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

it('shows an empty section to the owner only', function () {
    $talent = fuiTalentWithBlock(['is_published' => true], 'brand_collabs');

    $this->get('/'.$talent->slug)->assertOk()->assertDontSee(__('Nothing here yet.'));
    $this->actingAs($talent, 'talent')->get('/'.$talent->slug)->assertOk()->assertSee(__('Nothing here yet.'));
});

it('reports the completion steps from real data', function () {
    $talent = Talent::factory()->create(['display_name' => 'Nour', 'base_city' => 'Cairo', 'base_country' => null, 'bio' => ['en' => '']]);
    $service = app(ProfileCompletionService::class);

    expect($service->steps($talent))->toBe([
        'photo' => false, 'name' => true, 'place' => false, 'bio' => false, 'skill' => false, 'project' => false,
    ]);

    $type = TalentType::where('slug', 'photography')->firstOrFail();
    $talent->talentTypes()->attach($type->id, ['is_primary' => true, 'position' => 0]);
    $speciality = Speciality::factory()->create(['talent_type_id' => $type->id]);
    $talent->specialities()->attach($speciality->id);
    $talent->update(['base_country' => 'Egypt', 'bio' => ['en' => 'Ten years in fashion.']]);
    Project::factory()->for($talent)->create(['talent_type_id' => $type->id]);

    expect($service->steps($talent->fresh()))->toMatchArray([
        'place' => true, 'bio' => true, 'skill' => true, 'project' => true,
    ]);
});

it('shows the steps to go Live (with the verification step) on Profile structure while Private', function () {
    $talent = Talent::factory()->create(['is_published' => false, 'is_verified' => false]);

    $this->actingAs($talent, 'talent')->get(route('talent.profile.edit'))
        ->assertOk()
        ->assertSee('id="gate"', false)
        ->assertSee(__('Creatives Index'))
        ->assertSee(__('Checked by Fama'))
        ->assertSee(__('Go Live'));
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

it('shows no profile link on Profile structure while Private, and the link while Live', function () {
    $private = Talent::factory()->create(['slug' => 'hush-hush', 'is_published' => false]);
    $live = Talent::factory()->create(['slug' => 'out-loud', 'is_published' => true]);

    $html = $this->actingAs($private, 'talent')->get(route('talent.profile.edit'))->assertOk()->getContent();
    // The page body (the shared nav's own "Preview profile" link is not a share link).
    $main = substr($html, strpos($html, '<main'), strpos($html, '</main>') - strpos($html, '<main'));
    expect($main)->not->toContain('/hush-hush')
        ->toContain('<template x-if="isPublished">');

    $this->actingAs($live, 'talent')->get(route('talent.profile.edit'))->assertOk()
        ->assertSee('x-text="linkLabel"', false)
        ->assertSee(__('Make private'));
});

it('offers no share button on the owner preview of a private profile', function () {
    $talent = Talent::factory()->create(['is_published' => false]);

    $this->actingAs($talent, 'talent')->get('/'.$talent->slug)->assertOk()->assertDontSee(__('Share profile'));

    $talent->update(['is_published' => true]);
    $this->actingAs($talent, 'talent')->get('/'.$talent->slug)->assertOk()->assertSee(__('Share profile'));
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
