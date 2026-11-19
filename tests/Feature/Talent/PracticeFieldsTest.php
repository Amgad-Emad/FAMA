<?php

use App\Models\CompCard;
use App\Models\Digital;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\PracticeService;
use App\Services\SkillsService;
use App\Support\PracticeFields;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\SpecialitySeeder;
use Database\Seeders\TalentTypeSeeder;

/*
 * About your practice (ADR-AG) — the per-skill custom fields from the
 * prototype's fields.js: catalog, clean/validate, save, derived fields and the
 * public "About the practice" facts.
 */

beforeEach(function () {
    $this->withoutVite();
    $this->seed([TalentTypeSeeder::class, SpecialitySeeder::class, BlockTypeSeeder::class]);
});

function practiceTalent(string ...$skills): Talent
{
    $talent = Talent::factory()->create(['display_name' => 'Practice Person', 'is_published' => true]);
    foreach ($skills as $slug) {
        app(SkillsService::class)->addType($talent, TalentType::where('slug', $slug)->firstOrFail());
    }

    return $talent->fresh();
}

it('ports the prototype catalog: 27 fields across 31 skills, every skill slug real', function () {
    expect(PracticeFields::definitions())->toHaveCount(27)
        ->and(PracticeFields::bySkill())->toHaveCount(31); // the prototype's comment says 30; its map has 31

    $slugs = TalentType::pluck('slug')->all();
    foreach (array_keys(PracticeFields::bySkill()) as $slug) {
        expect($slugs)->toContain($slug);
    }
    expect(collect(PracticeFields::forSkill('modeling'))->pluck('id')->all())
        ->toBe(['height', 'measure', 'sizes', 'digitals', 'comfort']);
});

it('keeps only well-formed values of known fields and drops empties', function () {
    $clean = PracticeFields::clean([
        'height' => '178',
        'measure' => ['chest' => 96, 'waist' => 'x'],
        'kit' => 'Not an option',
        'dialects' => ['Egyptian' => 'Native', 'Klingon' => 'Native', 'Levantine' => 'Bad level'],
        'skintone' => [3, 11, 3],
        'langpairs' => [['English', 'Arabic'], ['English', 'English']],
        'comfort' => ['a' => ['Swimwear'], 'b' => []],
        'unknown' => 'x',
        'software' => ['Nuke'], // derived — never stored here
    ]);

    expect($clean)->toBe([
        'height' => 178,
        'measure' => ['chest' => 96],
        'comfort' => ['a' => ['Swimwear']],
        'skintone' => [3],
        'dialects' => ['Egyptian' => 'Native'],
        'langpairs' => [['English', 'Arabic']],
    ]);
});

it('saves the draft through the editor endpoint and syncs Software with the Software list', function () {
    $talent = practiceTalent('retouching-post');
    $talent->softwareStack()->create(['software_name' => 'Photoshop', 'position' => 0]); // hand-listed: left alone
    $talent->softwareStack()->create(['software_name' => 'Nuke', 'position' => 1]);

    $this->actingAs($talent, 'talent')
        ->putJson(route('talent.profile.practice'), ['fields' => ['height' => 180, 'software' => ['Capture One', 'Blender']]])
        ->assertOk()
        ->assertJsonPath('data.values.height', 180)
        ->assertJsonPath('data.values.software', ['Capture One', 'Blender']);

    expect($talent->fresh()->practice)->toBe(['height' => 180])
        ->and($talent->softwareStack()->pluck('software_name')->sort()->values()->all())
        ->toBe(['Blender', 'Capture One', 'Photoshop']);
});

it('answers a readable 422 for an out-of-range height or a playing age under five years wide', function () {
    $talent = practiceTalent('acting');

    $this->actingAs($talent, 'talent')
        ->putJson(route('talent.profile.practice'), ['fields' => ['height' => 400]])
        ->assertStatus(422)->assertJsonValidationErrors('fields.height');

    $this->actingAs($talent, 'talent')
        ->putJson(route('talent.profile.practice'), ['fields' => ['agerange' => ['min' => 20, 'max' => 22]]])
        ->assertStatus(422)->assertJsonValidationErrors('fields.agerange');
});

it('reads Digitals from the Digitals content, dated by the newest photo', function () {
    $talent = practiceTalent('modeling');
    Digital::factory()->for($talent)->create(['captured_at' => '2026-09-01']);
    Digital::factory()->for($talent)->create(['captured_at' => '2026-10-15']);

    $values = app(PracticeService::class)->values($talent->load(['softwareStack', 'digitals']));

    expect($values['digitals'])->toBe(['d' => '2026-10-15']);
});

it('renders About the practice in the skill tab, hiding Comfort and coverage from visitors', function () {
    $talent = practiceTalent('modeling');
    $talent->update(['practice' => PracticeFields::clean([
        'height' => 178,
        'comfort' => ['a' => ['Short sleeves']],
    ])]);
    $talent->profileBlocks()->where('talent_type_id', TalentType::where('slug', 'modeling')->value('id'))
        ->update(['is_visible' => true]);

    $html = $this->get('/'.$talent->slug)->assertOk()->getContent();
    expect($html)->toContain('About the practice')
        ->toContain('178 cm')
        ->not->toContain('Comfort and coverage');

    $this->actingAs($talent, 'talent')->get('/'.$talent->slug)->assertOk()
        ->assertSee('Comfort and coverage');
});

it('puts About your practice into each skill section of the editor', function () {
    $talent = practiceTalent('modeling');

    $this->actingAs($talent, 'talent')->get(route('talent.profile.edit'))->assertOk()
        ->assertSee(__('About your practice'))
        ->assertSee('mountFields($el, s)', false)
        ->assertSee('bySkill', false);
});

it('silences the old Comp Card once the practice holds height, measurements or sizes', function () {
    $talent = practiceTalent('modeling');
    CompCard::factory()->for($talent)->create(['height_cm' => 188, 'hair_color' => 'black']);
    $talent->profileBlocks()->update(['is_visible' => true]);

    $this->get('/'.$talent->slug)->assertOk()->assertSee('188 cm');

    $talent->update(['practice' => ['height' => 178]]);
    $html = $this->get('/'.$talent->slug)->assertOk()->getContent();
    expect($html)->toContain('178 cm')->not->toContain('188 cm');
});
