<?php

use App\Actions\Catalog\SyncSkillCatalog;
use App\Models\Project;
use App\Models\Speciality;
use App\Models\Talent;
use App\Models\TalentType;
use App\Models\User;
use App\Queries\TalentSearch;
use App\Services\SkillsService;
use App\Services\TalentProjectService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SpecialitySeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed([TalentTypeSeeder::class, SpecialitySeeder::class, BlockTypeSeeder::class]));

/**
 * Give a talent a skill through the service so blocks are seeded exactly as the
 * editor would seed them.
 */
function withSkill(Talent $talent, string $slug): TalentType
{
    $type = TalentType::where('slug', $slug)->firstOrFail();
    app(SkillsService::class)->addType($talent, $type);

    return $type;
}

// ----- The catalog itself ----------------------------------------------------

it('seeds the prototype’s 41 skills, each with four to eight specialities, exactly as the catalog lists them', function () {
    $skills = TalentType::with(['specialities' => fn ($q) => $q->where('is_active', true)->orderBy('position')])->get();

    expect($skills)->toHaveCount(41);

    foreach (SyncSkillCatalog::catalog() as $entry) {
        $skill = $skills->firstWhere('slug', $entry['slug']);
        expect($skill)->not->toBeNull()
            ->and($skill->getTranslation('name', 'en'))->toBe($entry['en'])
            ->and($skill->specialities->map(fn ($s) => $s->getTranslation('name', 'en'))->all())
            ->toBe(array_map(fn ($s) => $s[0], $entry['specialities']));
        expect($skill->specialities->count())->toBeGreaterThanOrEqual(4)->toBeLessThanOrEqual(8);
    }

    // Photography carries the widest range: eight.
    expect($skills->firstWhere('slug', 'photography')->specialities)->toHaveCount(8);
});

it('lets the same speciality name live under several skills', function () {
    // "Fashion" is a photography, modelling, styling AND retouching speciality —
    // the overlap is what lets one search assemble a whole crew.
    $fashion = Speciality::where('slug', 'fashion')->get();

    expect($fashion->count())->toBe(4)
        ->and($fashion->pluck('talent_type_id')->unique())->toHaveCount(4);
});

it('flags the entries added for the regional market', function () {
    $regional = Speciality::where('is_regional', true)->get()
        ->map(fn (Speciality $s) => $s->getTranslation('name', 'en'))
        ->unique()
        ->sort()
        ->values();

    expect($regional->all())->toBe([
        'Arabic calligraphy', 'Arabic to English', 'Arabic type design', 'Bilingual typesetting', 'Cultural consulting',
        'English to Arabic', 'Fixing & local coordination', 'Modest fashion', 'Permits & clearances',
    ]);
});

// ----- A talent claiming specialities ----------------------------------------

it('saves a talent’s speciality picks under one skill, in the order given', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $photography = withSkill($talent, 'photography');

    $picks = $photography->specialities()->take(3)->pluck('id')->reverse()->values();

    $this->actingAs($talent, 'talent')
        ->putJson(route('talent.profile.skills.specialities', $photography->id), ['speciality_ids' => $picks->all()])
        ->assertOk();

    expect($talent->fresh()->specialities->pluck('id')->all())->toBe($picks->all());
});

it('refuses a speciality that belongs to a different skill', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $photography = withSkill($talent, 'photography');

    // A real, selectable speciality — just not one of photography's.
    $foreign = Speciality::whereRelation('talentType', 'slug', 'graphic-design')->firstOrFail();

    $this->actingAs($talent, 'talent')
        ->putJson(route('talent.profile.skills.specialities', $photography->id), ['speciality_ids' => [$foreign->id]])
        ->assertStatus(422);

    expect($talent->fresh()->specialities)->toHaveCount(0);
});

it('refuses a retired speciality but keeps the claims already made on it', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $photography = withSkill($talent, 'photography');
    $speciality = $photography->specialities()->firstOrFail();

    app(SkillsService::class)->setSpecialities($talent, $photography, [$speciality->id]);

    $speciality->update(['is_active' => false]);

    // The existing claim survives — retiring is grandfathering, not a delete.
    expect($talent->fresh()->specialities->pluck('id')->all())->toBe([$speciality->id]);

    // But it can no longer be picked.
    $this->actingAs($talent, 'talent')
        ->putJson(route('talent.profile.skills.specialities', $photography->id), ['speciality_ids' => [$speciality->id]])
        ->assertStatus(422);
});

it('only replaces the picks under the skill being edited', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $photography = withSkill($talent, 'photography');
    $styling = withSkill($talent, 'styling');

    $photoPick = $photography->specialities()->firstOrFail();
    $stylePick = $styling->specialities()->firstOrFail();

    $service = app(SkillsService::class);
    $service->setSpecialities($talent, $photography, [$photoPick->id]);
    $service->setSpecialities($talent, $styling, [$stylePick->id]);

    // Clearing photography must leave styling's pick standing.
    $service->setSpecialities($talent, $photography, []);

    expect($talent->fresh()->specialities->pluck('id')->all())->toBe([$stylePick->id]);
});

it('drops a skill’s speciality claims when the skill is removed', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $photography = withSkill($talent, 'photography');
    withSkill($talent, 'styling');

    app(SkillsService::class)->setSpecialities($talent, $photography, [$photography->specialities()->firstOrFail()->id]);

    app(SkillsService::class)->removeType($talent, $photography);

    // A claim under a skill they no longer hold would put them in that skill's
    // discovery filter.
    expect($talent->fresh()->specialities)->toHaveCount(0);
});

// ----- A project drawing on specialities -------------------------------------

it('tags a project with specialities from its own skills', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $photography = withSkill($talent, 'photography');
    $picks = $photography->specialities()->take(2)->pluck('id')->all();

    $this->actingAs($talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'Riyadh editorial'],
        'skill_ids' => [$photography->id],
        'speciality_ids' => $picks,
    ])->assertCreated();

    expect(Project::firstOrFail()->specialities->pluck('id')->all())->toBe($picks);
});

it('refuses a project speciality that hangs off an untagged skill', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $photography = withSkill($talent, 'photography');
    $styling = withSkill($talent, 'styling');

    $this->actingAs($talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'Mismatch'],
        'skill_ids' => [$photography->id],
        'speciality_ids' => [$styling->specialities()->firstOrFail()->id],
    ])->assertStatus(422);

    expect(Project::count())->toBe(0);
});

it('prunes the picks that no longer hang off a tagged skill when the skills change', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $photography = withSkill($talent, 'photography');
    $styling = withSkill($talent, 'styling');

    $photoPick = $photography->specialities()->firstOrFail();
    $stylePick = $styling->specialities()->firstOrFail();

    $project = app(TalentProjectService::class)->create(
        $talent,
        ['title' => ['en' => 'Both']],
        [$photography->id, $styling->id],
        [$photoPick->id, $stylePick->id],
    );

    // Re-tag to photography alone WITHOUT restating the picks.
    app(TalentProjectService::class)->update($project, [], [$photography->id]);

    expect($project->fresh()->specialities->pluck('id')->all())->toBe([$photoPick->id]);
});

// ----- Discovery -------------------------------------------------------------

it('finds talents by speciality slug across the skills that share the name', function () {
    $photographer = Talent::factory()->create(['display_name' => 'Shooter', 'is_published' => true]);
    $stylist = Talent::factory()->create(['display_name' => 'Styler', 'is_published' => true]);
    $other = Talent::factory()->create(['display_name' => 'Nobody', 'is_published' => true]);

    $photography = withSkill($photographer, 'photography');
    $styling = withSkill($stylist, 'styling');
    withSkill($other, 'graphic-design');

    $service = app(SkillsService::class);
    $service->setSpecialities($photographer, $photography, [$photography->specialities()->where('slug', 'fashion')->firstOrFail()->id]);
    $service->setSpecialities($stylist, $styling, [$styling->specialities()->where('slug', 'fashion')->firstOrFail()->id]);

    $names = app(TalentSearch::class)->paginate(20)->getCollection()->pluck('display_name');

    // Unfiltered sanity: all three are published.
    expect($names)->toHaveCount(3);

    $this->get('/discover?filter[speciality]=fashion')->assertOk();

    request()->merge(['filter' => ['speciality' => 'fashion']]);
    $found = app(TalentSearch::class)->paginate(20)->getCollection()->pluck('display_name')->sort()->values();

    expect($found->all())->toBe(['Shooter', 'Styler']);
});

// ----- Admin governance ------------------------------------------------------

it('lets an admin add, retire and reinstate a speciality', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $skill = TalentType::where('slug', 'photography')->firstOrFail();

    $id = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.skills.specialities.store', $skill->id), [
            'name' => ['en' => 'Aerial & drone', 'ar' => 'التصوير الجوي'],
            'is_regional' => false,
        ])
        ->assertCreated()
        ->json('data.speciality.id');

    expect(Speciality::findOrFail($id)->slug)->toBe('aerial-drone');

    $this->actingAs($admin, 'admin')
        ->patchJson(route('admin.specialities.active', $id), ['is_active' => false])
        ->assertOk();

    expect(Speciality::findOrFail($id)->is_active)->toBeFalse();

    $this->actingAs($admin, 'admin')
        ->patchJson(route('admin.specialities.active', $id), ['is_active' => true])
        ->assertOk();

    expect(Speciality::findOrFail($id)->is_active)->toBeTrue();
});

it('refuses a duplicate speciality slug within one skill', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $skill = TalentType::where('slug', 'photography')->firstOrFail();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.skills.specialities.store', $skill->id), ['name' => ['en' => 'Fashion']])
        ->assertStatus(422);
});

it('has a glyph for every catalog skill in the one shared icon map', function () {
    $icons = json_decode(file_get_contents(resource_path('data/skill-icons.json')), true);

    foreach (TalentType::pluck('icon') as $icon) {
        expect($icons)->toHaveKey($icon);
    }
});
