<?php

use App\Models\BrandCollab;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\SkillsService;
use App\Support\Experience;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\SpecialitySeeder;
use Database\Seeders\TalentTypeSeeder;

/*
 * Experience as LinkedIn-style roles (ADR-AK) — the prototype's FAMA_EXP:
 * save through the Profile structure draft, validation, grouping by company
 * with durations, and the public render.
 */

beforeEach(function () {
    $this->withoutVite();
    $this->seed([TalentTypeSeeder::class, SpecialitySeeder::class, BlockTypeSeeder::class]);
});

function xpTalent(): Talent
{
    $talent = Talent::factory()->create(['display_name' => 'Role Person', 'is_published' => true]);
    app(SkillsService::class)->addType($talent, TalentType::where('slug', 'styling')->firstOrFail());

    return $talent->fresh();
}

it('saves the whole roles list from the editor, updating, adding and deleting', function () {
    $talent = xpTalent();
    $old = BrandCollab::factory()->for($talent)->create(['brand_name' => 'Old Co']);
    $gone = BrandCollab::factory()->for($talent)->create(['brand_name' => 'Gone Co']);
    $styling = $talent->talentTypes->first()->id;

    $this->actingAs($talent, 'talent')->putJson(route('talent.profile.experience'), ['roles' => [
        ['id' => $old->id, 't' => 'Lead stylist', 'n' => 'Old Co', 'et' => 'Freelance', 'cur' => true, 'sm' => 3, 'sy' => 2024, 'sk' => [$styling]],
        ['t' => 'Stylist', 'n' => 'New Co', 'sm' => 1, 'sy' => 2020, 'em' => 6, 'ey' => 2022, 'hl' => "One\nTwo"],
    ]])->assertOk()->assertJsonCount(2, 'data.roles');

    expect(BrandCollab::find($gone->id))->toBeNull()
        ->and($old->fresh()->role_title)->toBe('Lead stylist')
        ->and($old->fresh()->is_current)->toBeTrue()
        ->and($talent->brandCollabs()->where('brand_name', 'New Co')->value('end_year'))->toBe(2022);
});

it('needs a title, a company and a start year, and an end after the start', function () {
    $talent = xpTalent();

    $this->actingAs($talent, 'talent')->putJson(route('talent.profile.experience'), ['roles' => [
        ['t' => '', 'n' => '', 'sy' => null],
    ]])->assertStatus(422)->assertJsonValidationErrors(['roles.0.t', 'roles.0.n', 'roles.0.sy']);

    $this->actingAs($talent, 'talent')->putJson(route('talent.profile.experience'), ['roles' => [
        ['t' => 'Stylist', 'n' => 'Co', 'sm' => 6, 'sy' => 2024, 'em' => 1, 'ey' => 2023],
    ]])->assertStatus(422)->assertJsonValidationErrors('roles.0.ey');
});

it('groups consecutive roles at one company, newest first, with durations', function () {
    $talent = xpTalent();
    $talent->brandCollabs()->createMany([
        ['brand_name' => 'Nomad', 'role_title' => 'Stylist', 'start_month' => 1, 'start_year' => 2021, 'end_month' => 5, 'end_year' => 2022],
        ['brand_name' => 'Nomad', 'role_title' => 'Senior stylist', 'start_month' => 6, 'start_year' => 2022, 'end_month' => 12, 'end_year' => 2023],
        ['brand_name' => 'Maison Nour', 'role_title' => 'Lead stylist', 'is_current' => true, 'start_month' => 3, 'start_year' => 2024],
    ]);

    $groups = Experience::groups($talent->brandCollabs()->get());

    expect(array_column($groups, 'n'))->toBe(['Maison Nour', 'Nomad'])
        ->and(array_column($groups[1]['roles'], 't'))->toBe(['Senior stylist', 'Stylist'])
        ->and(Experience::range($groups[1]['roles'][0]))->toBe('Jun 2022 – Dec 2023 · 1 yr 7 mos')
        ->and(Experience::duration(Experience::groupMonths($groups[1]['roles'])))->toBe('3 yrs');
});

it('renders the roles on the public profile, grouped, with highlights and skills', function () {
    $talent = xpTalent();
    $talent->profileBlocks()->update(['is_visible' => true]);
    $talent->brandCollabs()->create(['brand_name' => 'Maison Nour', 'role_title' => 'Lead stylist', 'employment_type' => 'Freelance',
        'is_current' => true, 'start_month' => 3, 'start_year' => 2024, 'highlights' => "Red Sea capsule\n· Modest lookbook",
        'skill_ids' => [$talent->talentTypes->first()->id]]);

    $this->get('/'.$talent->slug)->assertOk()
        ->assertSee('Lead stylist')
        ->assertSee('Maison Nour · Freelance')
        ->assertSee('Mar 2024 – Present', false)
        ->assertSee('Modest lookbook')
        ->assertSee('Styling');
});

it('reads a row written before roles existed, with its project as the title', function () {
    $row = BrandCollab::factory()->for(xpTalent())->create(['brand_name' => 'Old Co', 'project_title' => ['en' => 'Spring campaign'], 'year' => 2023]);
    $row->update(['start_year' => 2023]);

    expect(Experience::role($row->fresh()))->toMatchArray(['t' => 'Spring campaign', 'n' => 'Old Co', 'sy' => 2023]);
});

it('puts the role dialog on the editor', function () {
    $this->actingAs(xpTalent(), 'talent')->get(route('talent.profile.edit'))->assertOk()
        ->assertSee('openRole(null)', false)
        ->assertSee(__('I currently work here'))
        ->assertSee('xpHtml()', false);
});
