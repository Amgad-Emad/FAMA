<?php

use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\SkillsService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The talent Projects page (ADR-X): one page owns projects, and a project must
 * carry at least one of the talent's own skills.
 */

/** A talent with the modeling + photography skills attached. */
function projectTalent(): array
{
    test()->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);

    $talent = Talent::factory()->create();
    $modeling = TalentType::where('slug', 'modeling')->firstOrFail();
    $photography = TalentType::where('slug', 'photography')->firstOrFail();

    $talent->talentTypes()->attach([
        $modeling->id => ['is_primary' => true, 'position' => 0],
        $photography->id => ['is_primary' => false, 'position' => 1],
    ]);

    return [$talent, $modeling, $photography];
}

it('renders the Projects page with the talent\'s skills', function () {
    [$talent] = projectTalent();

    $this->actingAs($talent, 'talent')->get(route('talent.projects'))
        ->assertOk()
        ->assertSee('Projects', false)
        ->assertSee('talentProjects(', false)
        ->assertSee('Modeling', false)
        ->assertSee('Photography', false);
});

it('creates a project with several skills, mirroring the first onto the home-tab column', function () {
    [$talent, $modeling, $photography] = projectTalent();

    $id = $this->actingAs($talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'Ramadan film', 'ar' => 'فيلم رمضان'],
        'role' => ['en' => 'Director of photography'],
        'client_name' => 'Almarai',
        'year' => 2026,
        'url' => 'https://example.com/film',
        'summary' => ['en' => '<p>A <b>short</b> film.</p><script>alert(1)</script>'],
        'results' => [['label' => 'Reach', 'value' => '2.4M'], ['label' => '', 'value' => 'dropped']],
        'skill_ids' => [$photography->id, $modeling->id],
    ])->assertCreated()
        ->assertJsonPath('data.skill_ids', [$photography->id, $modeling->id])
        ->assertJsonPath('data.talent_type_id', $photography->id)
        ->assertJsonPath('data.results', [['label' => 'Reach', 'value' => '2.4M']])
        ->json('data.id');

    $project = Project::findOrFail($id);

    expect($project->talent_id)->toBe($talent->id)
        ->and($project->talentTypes()->pluck('talent_types.id')->all())->toBe([$photography->id, $modeling->id])
        // Rich text is sanitized with the shared allowlist on the way in.
        ->and($project->getTranslation('summary', 'en'))->not->toContain('<script>')
        ->and($project->getTranslation('title', 'ar'))->toBe('فيلم رمضان');
});

it('refuses a project with no skill, and one tagged with a skill the talent does not have', function () {
    [$talent] = projectTalent();
    $foreign = TalentType::where('slug', 'styling')->firstOrFail();

    $this->actingAs($talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'No skill'],
        'skill_ids' => [],
    ])->assertStatus(422)->assertJsonPath('errors.skill_ids.0', 'Pick at least one skill for this project.');

    $this->actingAs($talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'Missing skills key'],
    ])->assertStatus(422)->assertJsonValidationErrors('skill_ids');

    $this->actingAs($talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'Foreign skill'],
        'skill_ids' => [$foreign->id],
    ])->assertStatus(422)->assertJsonValidationErrors('skill_ids.0');

    expect(Project::count())->toBe(0);
});

it('rejects the service call directly when the skill list is empty (the rule lives in the service)', function () {
    [$talent] = projectTalent();

    expect(fn () => app(\App\Services\TalentProjectService::class)->create($talent, ['title' => ['en' => 'X']], []))
        ->toThrow(InvalidArgumentException::class);
});

it('updates a project and can never leave it with zero skills', function () {
    [$talent, $modeling, $photography] = projectTalent();

    $project = Project::factory()->for($talent)->create(['talent_type_id' => $modeling->id]);

    $this->actingAs($talent, 'talent')->patchJson(route('talent.projects.update', $project), [
        'title' => ['en' => 'Renamed'],
        'skill_ids' => [$photography->id],
    ])->assertOk()
        ->assertJsonPath('data.label', 'Renamed')
        ->assertJsonPath('data.talent_type_id', $photography->id)
        ->assertJsonPath('data.skill_ids', [$photography->id]);

    $this->actingAs($talent, 'talent')->patchJson(route('talent.projects.update', $project), [
        'title' => ['en' => 'Renamed'],
        'skill_ids' => [],
    ])->assertStatus(422);

    expect($project->fresh()->talentTypes()->count())->toBe(1);
});

it('lists, filters and counts projects by skill without touching another talent\'s work', function () {
    [$talent, $modeling, $photography] = projectTalent();
    [$other] = projectTalent();

    $both = Project::factory()->for($talent)->create(['talent_type_id' => $modeling->id, 'position' => 0]);
    $both->talentTypes()->attach($photography->id, ['position' => 1]);

    $photoOnly = Project::factory()->for($talent)->create(['talent_type_id' => $photography->id, 'position' => 1]);

    $untagged = Project::factory()->for($talent)->create(['talent_type_id' => null, 'position' => 2]);

    Project::factory()->for($other)->create();

    $this->actingAs($talent, 'talent')->getJson(route('talent.projects.data'))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.counts.all', 3)
        ->assertJsonPath('meta.counts.none', 1)
        ->assertJsonPath("meta.counts.by_skill.{$photography->id}", 2)
        ->assertJsonPath("meta.counts.by_skill.{$modeling->id}", 1);

    $this->actingAs($talent, 'talent')->getJson(route('talent.projects.data', ['skill' => $modeling->id]))
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $both->id);

    $this->actingAs($talent, 'talent')->getJson(route('talent.projects.data', ['skill' => 'none']))
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $untagged->id);
});

it('403s on another talent\'s project and 404s the page for a guest', function () {
    [$talent, $modeling] = projectTalent();
    [$other] = projectTalent();

    $foreign = Project::factory()->for($other)->create();

    $this->actingAs($talent, 'talent')->patchJson(route('talent.projects.update', $foreign), [
        'title' => ['en' => 'Hijack'],
        'skill_ids' => [$modeling->id],
    ])->assertForbidden();

    $this->actingAs($talent, 'talent')->deleteJson(route('talent.projects.destroy', $foreign))->assertForbidden();
});

// Separate test on purpose: a test session can hold several guards at once, so an
// earlier actingAs() would leak through the guest assertion.
it('sends a guest to the login page', function () {
    $this->get(route('talent.projects'))->assertRedirect(route('login'));
});

it('uploads and removes a cover through the media library', function () {
    Storage::fake('public');
    [$talent, $modeling] = projectTalent();

    $project = Project::factory()->for($talent)->create(['talent_type_id' => $modeling->id]);

    $this->actingAs($talent, 'talent')->post(route('talent.projects.cover.store', $project), [
        'cover' => UploadedFile::fake()->image('cover.jpg', 1600, 900),
    ])->assertOk()->assertJsonPath('success', true);

    expect($project->fresh()->getFirstMedia('cover'))->not->toBeNull();

    $this->actingAs($talent, 'talent')->deleteJson(route('talent.projects.cover.destroy', $project))
        ->assertOk()->assertJsonPath('data.cover_image_url', null);

    expect($project->fresh()->getFirstMedia('cover'))->toBeNull();
});

it('reorders the talent\'s own projects and refuses a foreign id', function () {
    [$talent, $modeling] = projectTalent();
    [$other] = projectTalent();

    $a = Project::factory()->for($talent)->create(['talent_type_id' => $modeling->id, 'position' => 0]);
    $b = Project::factory()->for($talent)->create(['talent_type_id' => $modeling->id, 'position' => 1]);
    $foreign = Project::factory()->for($other)->create();

    $this->actingAs($talent, 'talent')->patchJson(route('talent.projects.reorder'), ['order' => [$b->id, $a->id]])
        ->assertOk();

    expect($a->fresh()->position)->toBe(1)->and($b->fresh()->position)->toBe(0);

    $this->actingAs($talent, 'talent')->patchJson(route('talent.projects.reorder'), ['order' => [$foreign->id]])
        ->assertStatus(422);
});

it('deletes a project and its tags', function () {
    [$talent, $modeling] = projectTalent();

    $project = Project::factory()->for($talent)->create(['talent_type_id' => $modeling->id]);

    $this->actingAs($talent, 'talent')->deleteJson(route('talent.projects.destroy', $project))->assertOk();

    expect(Project::find($project->id))->toBeNull()
        ->and(\Illuminate\Support\Facades\DB::table('project_talent_type')->where('project_id', $project->id)->count())->toBe(0);
});

it('preserves a project when its skill is removed, re-homing it on the next tag', function () {
    [$talent, $modeling, $photography] = projectTalent();

    $both = Project::factory()->for($talent)->create(['talent_type_id' => $modeling->id]);
    $both->talentTypes()->attach($photography->id, ['position' => 1]);

    $only = Project::factory()->for($talent)->create(['talent_type_id' => $modeling->id]);

    app(SkillsService::class)->removeType($talent, $modeling);

    // The multi-skill project keeps its other tag and re-homes onto it…
    expect($both->fresh()->talentTypes()->pluck('talent_types.id')->all())->toBe([$photography->id])
        ->and($both->fresh()->talent_type_id)->toBe($photography->id)
        // …and the single-skill one survives, un-scoped (ADR-Q: content is preserved).
        ->and($only->fresh()->talentTypes()->count())->toBe(0)
        ->and($only->fresh()->talent_type_id)->toBeNull();
});

it('creates a project with no cover at all (the cover is optional)', function () {
    [$talent, $modeling] = projectTalent();

    $id = $this->actingAs($talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'Coverless'],
        'skill_ids' => [$modeling->id],
    ])->assertCreated()->assertJsonPath('data.cover_image_url', null)->json('data.id');

    expect(Project::findOrFail($id)->getFirstMedia('cover'))->toBeNull();
});

it('never stores a blank Arabic translation (V1 has no Arabic inputs), and keeps one already stored', function () {
    [$talent, $modeling] = projectTalent();

    // What the V1 composer actually submits: English filled, `ar` blank.
    $id = $this->actingAs($talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'English only', 'ar' => ''],
        'role' => ['en' => 'Stylist', 'ar' => ''],
        'skill_ids' => [$modeling->id],
    ])->assertCreated()->json('data.id');

    $project = Project::findOrFail($id);

    // The locale is ABSENT, not blank — so /ar falls back to English instead of
    // rendering an empty title.
    expect($project->getTranslations('title'))->toBe(['en' => 'English only'])
        ->and($project->getTranslations('role'))->toBe(['en' => 'Stylist'])
        ->and($project->getTranslation('title', 'ar'))->toBe('English only');

    // An Arabic title stored earlier (seed, import, a later AR pass) survives an
    // edit made through the English-only editor.
    $project->setTranslation('title', 'ar', 'عنوان عربي')->save();

    $this->actingAs($talent, 'talent')->patchJson(route('talent.projects.update', $project), [
        'title' => ['en' => 'Renamed in English', 'ar' => ''],
        'skill_ids' => [$modeling->id],
    ])->assertOk();

    expect($project->fresh()->getTranslations('title'))
        ->toEqualCanonicalizing(['ar' => 'عنوان عربي', 'en' => 'Renamed in English']);
});

it('still clears an English translation when the visible input is blanked', function () {
    [$talent, $modeling] = projectTalent();

    $project = Project::factory()->for($talent)->create(['talent_type_id' => $modeling->id]);

    $this->actingAs($talent, 'talent')->patchJson(route('talent.projects.update', $project), [
        'title' => ['en' => 'Kept'],
        'summary' => ['en' => ''],
        'skill_ids' => [$modeling->id],
    ])->assertOk();

    expect($project->fresh()->getTranslation('summary', 'en', false))->toBe('');
});

it('does not render an Arabic title input in the composer (V1)', function () {
    [$talent] = projectTalent();

    $this->actingAs($talent, 'talent')->get(route('talent.projects'))
        ->assertOk()
        ->assertDontSee('pc-title-ar', false)
        ->assertDontSee('Title (Arabic)', false)
        // The cover reads as optional rather than a mandatory-looking dropzone.
        ->assertSee('Optional', false);
});
