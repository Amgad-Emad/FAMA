<?php

use App\Models\Recognition;
use App\Models\Speciality;
use App\Models\Talent;
use App\Models\TalentType;
use App\Models\User;
use App\Queries\TalentSearch;
use App\Services\RecognitionService;
use App\Services\SkillsService;
use App\Services\TalentInterestService;
use App\Services\TalentProfileService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SpecialitySeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Foundation\Testing\WithFaker;

uses(WithFaker::class);

beforeEach(fn () => $this->seed([TalentTypeSeeder::class, SpecialitySeeder::class, BlockTypeSeeder::class]));

/**
 * A published, verified talent ready to be looked at or vouched for.
 *
 * Named distinctly because Pest test helpers share ONE global namespace —
 * `publishedTalent()` already exists in Public/TalentProfileFollowTest.
 */
function livePortfolioTalent(string $name): Talent
{
    return Talent::factory()->create([
        'display_name' => $name,
        'is_published' => true,
        'is_verified' => true,
    ]);
}

// ----- NOW -------------------------------------------------------------------

it('stamps the availability date only when availability actually flips', function () {
    $talent = Talent::factory()->create(['display_name' => 'A', 'is_available' => true]);

    expect($talent->availability_changed_at)->toBeNull();

    // Re-saving the SAME value must not refresh the stamp — the whole point of
    // the date is that it tells the truth about when this last changed.
    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.now'), ['is_available' => true])
        ->assertOk();

    expect($talent->fresh()->availability_changed_at)->toBeNull();

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.now'), ['is_available' => false])
        ->assertOk()
        ->assertJsonPath('data.is_available', false);

    expect($talent->fresh()->availability_changed_at)->not->toBeNull();
});

it('saves the travel toggle without touching the availability stamp', function () {
    $talent = Talent::factory()->create(['display_name' => 'A']);

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.now'), ['will_travel' => true])
        ->assertOk()
        ->assertJsonPath('data.will_travel', true);

    $talent->refresh();

    expect($talent->will_travel)->toBeTrue()
        ->and($talent->availability_changed_at)->toBeNull();
});

it('shows availability with its last-changed date on the public profile', function () {
    $talent = livePortfolioTalent('Layla');
    app(TalentProfileService::class)->updateNow($talent, ['is_available' => false, 'will_travel' => true]);

    $this->get(url($talent->slug))
        ->assertOk()
        ->assertSee(__('Not open for work'))
        ->assertSee(__('Changed :when', ['when' => $talent->fresh()->availability_changed_at->diffForHumans()]))
        ->assertSee(__('Will travel'));
});

// ----- ABOUT -----------------------------------------------------------------

it('keeps worldview separate from bio', function () {
    $talent = Talent::factory()->create(['display_name' => 'A']);

    $this->actingAs($talent, 'talent')->patchJson(route('talent.profile.update'), [
        'bio' => ['en' => 'Ten years shooting fashion in Cairo.'],
        'worldview' => ['en' => 'I turn down anything that needs a model to look bored.'],
    ])->assertOk();

    $talent->refresh();

    expect($talent->getTranslation('bio', 'en'))->toBe('Ten years shooting fashion in Cairo.')
        ->and($talent->getTranslation('worldview', 'en'))->toBe('I turn down anything that needs a model to look bored.');
});

it('replaces social handles as one ordered list and rejects a duplicate platform', function () {
    $talent = Talent::factory()->create(['display_name' => 'A']);

    $this->actingAs($talent, 'talent')->putJson(route('talent.profile.social'), ['handles' => [
        ['platform' => 'instagram', 'handle' => '@layla', 'url' => 'https://instagram.com/layla'],
        ['platform' => 'behance', 'handle' => 'layla', 'url' => 'https://behance.net/layla'],
    ]])->assertOk();

    expect($talent->fresh()->socialHandles->pluck('platform')->all())->toBe(['instagram', 'behance']);

    // Two rows for one platform is a mistake, caught at the request boundary
    // rather than as a unique-index exception.
    $this->actingAs($talent, 'talent')->putJson(route('talent.profile.social'), ['handles' => [
        ['platform' => 'instagram', 'handle' => 'a', 'url' => 'https://instagram.com/a'],
        ['platform' => 'instagram', 'handle' => 'b', 'url' => 'https://instagram.com/b'],
    ]])->assertStatus(422);

    // An empty list is a legitimate "clear them all".
    $this->actingAs($talent, 'talent')
        ->putJson(route('talent.profile.social'), ['handles' => []])
        ->assertOk();

    expect($talent->fresh()->socialHandles)->toHaveCount(0);
});

// ----- RECOGNITIONS ----------------------------------------------------------

it('lets a talent vouch for a peer, landing pending for moderation', function () {
    $subject = livePortfolioTalent('Layla');
    $peer = livePortfolioTalent('Omar');

    $this->actingAs($peer, 'talent')->postJson(route('talent.recognition.store', ['slug' => $subject->slug]), [
        'relationship' => 'Shot three campaigns together',
        'body' => str_repeat('She reads a set faster than anyone I have worked with. ', 2),
    ])->assertCreated();

    $recognition = Recognition::firstOrFail();

    expect($recognition->talent_id)->toBe($subject->id)
        ->and($recognition->author_talent_id)->toBe($peer->id)
        ->and($recognition->status->getValue())->toBe('pending')
        ->and($recognition->is_approved)->toBeFalse();
});

it('refuses a self-recognition', function () {
    $talent = livePortfolioTalent('Layla');

    $this->actingAs($talent, 'talent')->postJson(route('talent.recognition.store', ['slug' => $talent->slug]), [
        'relationship' => 'I know myself quite well',
        'body' => str_repeat('I am extremely good at what I do and everybody says so. ', 2),
    ])->assertStatus(422);

    expect(Recognition::count())->toBe(0);
});

it('sends a guest to the talent login rather than the recognition form', function () {
    $subject = livePortfolioTalent('Layla');

    $this->get(route('talent.recognition.create', ['slug' => $subject->slug]))
        ->assertRedirect();
});

it('replaces an earlier vouch and sends it back through moderation', function () {
    $subject = livePortfolioTalent('Layla');
    $peer = livePortfolioTalent('Omar');
    $service = app(RecognitionService::class);

    $first = $service->submit($subject, $peer, [
        'relationship' => 'Shot three campaigns together',
        'body' => 'The original wording of the vouch, which was fine at the time.',
    ]);
    $service->approve($first);

    expect($first->fresh()->is_approved)->toBeTrue();

    $second = $service->submit($subject, $peer, [
        'relationship' => 'Shot four campaigns together',
        'body' => 'A reworded vouch that must not inherit the earlier approval.',
    ]);

    // Same row (the unique index holds), reset to pending.
    expect($second->id)->toBe($first->id)
        ->and(Recognition::count())->toBe(1)
        ->and($second->status->getValue())->toBe('pending')
        ->and($second->is_approved)->toBeFalse();
});

it('only shows approved recognitions on the public profile', function () {
    $subject = livePortfolioTalent('Layla');
    $peer = livePortfolioTalent('Omar');

    // The section is a BLOCK (ADR-Z maps the spec onto the block system), and
    // blocks are seeded with the talent's first skill — a profile with no
    // skills has no sections at all, which is correct but renders nothing.
    app(SkillsService::class)->addType($subject, TalentType::where('slug', 'photography')->firstOrFail());

    $pending = app(RecognitionService::class)->submit($subject, $peer, [
        'relationship' => 'Shot three campaigns together',
        'body' => 'A vouch that has not been through moderation yet at all.',
    ]);

    $this->get(url($subject->slug))->assertOk()->assertDontSee('A vouch that has not been through moderation');

    app(RecognitionService::class)->approve($pending);

    $this->get(url($subject->slug))->assertOk()->assertSee('A vouch that has not been through moderation');
});

it('syncs is_approved from the state machine, never by hand', function () {
    $recognition = Recognition::factory()->create();

    app(RecognitionService::class)->approve($recognition);
    expect($recognition->fresh()->is_approved)->toBeTrue();

    app(RecognitionService::class)->reject($recognition);
    expect($recognition->fresh()->is_approved)->toBeFalse();
});

it('lets an admin moderate the recognitions queue', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $recognition = Recognition::factory()->create();

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.moderation.recognitions'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $recognition->id);

    $this->actingAs($admin, 'admin')
        ->patchJson(route('admin.moderation.recognitions.action', [$recognition->id, 'approve']))
        ->assertOk();

    expect($recognition->fresh()->is_approved)->toBeTrue();
});

// ----- WILLING TO EXPLORE ----------------------------------------------------

it('lets a talent name a direction in a skill they do NOT hold', function () {
    $talent = Talent::factory()->create(['display_name' => 'A']);
    app(SkillsService::class)->addType($talent, TalentType::where('slug', 'modeling')->firstOrFail());

    // Directing is not one of their skills — which is the entire point.
    $direction = TalentType::where('slug', 'direction')->firstOrFail();

    $this->actingAs($talent, 'talent')->postJson(route('talent.profile.interests.store'), [
        'talent_type_id' => $direction->id,
    ])->assertCreated();

    expect($talent->fresh()->interests)->toHaveCount(1)
        ->and($talent->fresh()->interests->first()->talent_type_id)->toBe($direction->id);
});

it('refuses a kind of work that does not belong to the chosen skill', function () {
    $talent = Talent::factory()->create(['display_name' => 'A']);
    $direction = TalentType::where('slug', 'direction')->firstOrFail();
    $foreign = Speciality::whereRelation('talentType', 'slug', 'graphic-design')->firstOrFail();

    $this->actingAs($talent, 'talent')->postJson(route('talent.profile.interests.store'), [
        'talent_type_id' => $direction->id,
        'speciality_id' => $foreign->id,
    ])->assertStatus(422);

    expect($talent->fresh()->interests)->toHaveCount(0);
});

it('refuses the same direction twice but allows narrowing the same skill', function () {
    $talent = Talent::factory()->create(['display_name' => 'A']);
    $service = app(TalentInterestService::class);
    $photography = TalentType::where('slug', 'photography')->firstOrFail();
    $food = $photography->specialities()->where('slug', 'food')->firstOrFail();

    $service->create($talent, ['talent_type_id' => $photography->id]);

    // The whole skill AND one kind of work under it are different statements.
    $service->create($talent, ['talent_type_id' => $photography->id, 'speciality_id' => $food->id]);

    expect($talent->fresh()->interests)->toHaveCount(2);

    // The identical pair is a duplicate, not a stronger signal.
    expect(fn () => $service->create($talent, ['talent_type_id' => $photography->id]))
        ->toThrow(InvalidArgumentException::class);
});

it('caps the list so it does not read as a wish list', function () {
    $talent = Talent::factory()->create(['display_name' => 'A']);
    $service = app(TalentInterestService::class);

    foreach (TalentType::query()->take(6)->pluck('id') as $id) {
        $service->create($talent, ['talent_type_id' => $id]);
    }

    $seventh = TalentType::query()->whereNotIn('id', $talent->interests->pluck('talent_type_id'))->firstOrFail();

    expect(fn () => $service->create($talent, ['talent_type_id' => $seventh->id]))
        ->toThrow(InvalidArgumentException::class);
});

// ----- DISCOVERY -------------------------------------------------------------

it('filters discovery by availability and by a stated direction', function () {
    $free = livePortfolioTalent('Free');
    $busy = livePortfolioTalent('Busy');

    app(TalentProfileService::class)->updateNow($busy, ['is_available' => false]);

    $direction = TalentType::where('slug', 'direction')->firstOrFail();
    app(TalentInterestService::class)->create($free, ['talent_type_id' => $direction->id]);

    request()->merge(['filter' => ['available' => '1']]);
    expect(app(TalentSearch::class)->paginate(20)->getCollection()->pluck('display_name')->all())
        ->toBe(['Free']);

    request()->replace(['filter' => ['open_to' => 'direction']]);
    expect(app(TalentSearch::class)->paginate(20)->getCollection()->pluck('display_name')->all())
        ->toBe(['Free']);
});

// ----- BLOCK SEEDING ---------------------------------------------------------

it('seeds the profile-level sections once, not once per skill', function () {
    $talent = Talent::factory()->create(['display_name' => 'A']);
    $skills = app(SkillsService::class);

    $skills->addType($talent, TalentType::where('slug', 'photography')->firstOrFail());
    $skills->addType($talent, TalentType::where('slug', 'modeling')->firstOrFail());

    $keys = $talent->fresh()->profileBlocks()->whereNull('talent_type_id')
        ->with('blockType')->get()->map(fn ($b) => $b->blockType->key);

    // Recognitions and Willing to explore describe the PERSON, so they belong
    // in the universal region exactly once — never repeated per skill tab.
    expect($keys->filter(fn ($k) => $k === 'recognitions'))->toHaveCount(1)
        ->and($keys->filter(fn ($k) => $k === 'interests'))->toHaveCount(1);

    expect($talent->fresh()->profileBlocks()->whereNotNull('talent_type_id')
        ->whereRelation('blockType', 'key', 'recognitions')->count())->toBe(0);
});

it('takes a long, multi-line note up to 2,000 characters and refuses more', function () {
    $talent = Talent::factory()->create(['display_name' => 'A']);
    $direction = TalentType::where('slug', 'direction')->firstOrFail();
    $note = str_repeat('Would love to shape a campaign end to end. ', 40)."\nSecond line."; // well past the old 160 / 255

    $this->actingAs($talent, 'talent')->postJson(route('talent.profile.interests.store'), [
        'talent_type_id' => $direction->id, 'note' => $note,
    ])->assertCreated();
    expect($talent->fresh()->interests->first()->note)->toBe($note);

    $this->actingAs($talent, 'talent')->postJson(route('talent.profile.interests.store'), [
        'talent_type_id' => TalentType::where('slug', 'styling')->firstOrFail()->id, 'note' => str_repeat('x', 2001),
    ])->assertStatus(422);
});

it('edits the Willing to explore note in a text area, without the old 160 limit', function () {
    $this->actingAs(Talent::factory()->create(), 'talent')->get(route('talent.profile.edit'))
        ->assertOk()
        ->assertSee('<textarea class="ta" maxlength="2000" x-model="w.note"', false)
        ->assertDontSee("' / 160'", false);
});
