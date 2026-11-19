<?php

use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
});

/** A published talent with one skill (with a visible block) and one universal block. */
function talentWithSections(): Talent
{
    $talent = Talent::factory()->create(['slug' => 'sections-talent', 'is_published' => true]);
    $type = TalentType::query()->first();
    $talent->talentTypes()->attach($type->id, ['is_primary' => true, 'position' => 0]);

    // Skip `hero` — the public profile deliberately never renders it (ADR-O).
    $universalType = BlockType::query()->where('availability', 'universal')->where('key', '!=', 'hero')->first();
    ProfileBlock::factory()->for($talent)->create([
        'block_type_id' => $universalType->id, 'talent_type_id' => null, 'is_visible' => true, 'position' => 0,
    ]);
    ProfileBlock::factory()->for($talent)->create([
        'block_type_id' => $universalType->id, 'talent_type_id' => $type->id, 'is_visible' => true, 'position' => 0,
    ]);

    return $talent->fresh();
}

it('persists the universal section position from the editor pill bar', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.sections.universal'), ['position' => 2])
        ->assertOk()
        ->assertJsonPath('data.universal_position', 2);

    expect($talent->fresh()->meta['universal_position'])->toBe(2);

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.sections.universal'), ['position' => -1])
        ->assertStatus(422);
});

it('renders the profile-wide region once, after the skill tabs (fama-ui 24 Oct 2026)', function () {
    $talent = talentWithSections();

    // The owner sees the (still empty) profile-wide sections; the point of view lives in the hero now.
    $html = $this->actingAs($talent, 'talent')->get('/'.$talent->slug)->assertOk()->getContent();

    expect(strpos($html, 'id="universal-blocks"'))->not->toBeFalse()
        ->toBeGreaterThan(strpos($html, 'x-ref="panel"'));
    expect(substr_count($html, 'id="universal-blocks"'))->toBe(1);
});

it('keeps the profile-wide region after the tabs even when placed first in the editor', function () {
    $talent = talentWithSections();
    $talent->update(['meta' => ['universal_position' => 0]]);

    $html = $this->actingAs($talent, 'talent')->get('/'.$talent->slug)->assertOk()->getContent();

    expect(strpos($html, 'id="universal-blocks"'))->toBeGreaterThan(strpos($html, 'x-ref="panel"'));
});

it('no longer offers the profile-wide placement control (the prototype fixes it after the tabs)', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->get(route('talent.profile.edit'))
        ->assertOk()
        ->assertSee('id="sections"', false)
        ->assertDontSee('setPlace(', false)
        ->assertDontSee(__('Before your work'));
});

it('shows the share-profile button on the public header', function () {
    $talent = Talent::factory()->create(['is_published' => true]);

    $this->get('/'.$talent->slug)
        ->assertOk()
        ->assertSee(__('Share profile'))
        ->assertSee(__('Recognise'))
        ->assertDontSee(__('Leave a review')); // reviews are off on the public page (features.reviews)

    config(['features.reviews' => true]);
    $this->get('/'.$talent->slug)->assertOk()->assertSee(__('Leave a review'));
});
