<?php

use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]));

function editorTalent(): Talent
{
    $talent = Talent::factory()->create(['display_name' => 'Owner']);
    $talent->talentTypes()->attach(TalentType::where('slug', 'modeling')->firstOrFail()->id, ['is_primary' => true, 'position' => 0]);

    return $talent->load('talentTypes', 'profileBlocks');
}

it('renders the consolidated profile editor (skills, username, publishing, pricing)', function () {
    $talent = editorTalent();

    $this->actingAs($talent, 'talent')->withoutVite()->get(route('talent.profile.edit'))
        ->assertOk()
        ->assertSee(__('Skills'))
        ->assertSee(__('Username'))
        ->assertSee(__('Publishing'))
        ->assertSee(__('Pricing rate'))
        ->assertDontSee(__('Professions'));
});

it('updates core fields via the envelope', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.update'), ['display_name' => 'Renamed', 'headline' => ['en' => 'Model']])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($talent->fresh()->display_name)->toBe('Renamed');
    expect($talent->fresh()->getTranslation('headline', 'en'))->toBe('Model');
});

it('adds an eligible block, fills, toggles, reorders and removes it', function () {
    $talent = editorTalent();
    $gallery = BlockType::where('key', 'gallery')->firstOrFail();

    $blockId = $this->actingAs($talent, 'talent')
        ->postJson(route('talent.profile.blocks.store'), ['block_type_id' => $gallery->id])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.blocks.update', $blockId), ['title' => ['en' => 'My work']])
        ->assertOk();
    expect(ProfileBlock::find($blockId)->getTranslation('title', 'en'))->toBe('My work');

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.blocks.visibility', $blockId), ['is_visible' => false])
        ->assertOk();
    expect(ProfileBlock::find($blockId)->is_visible)->toBeFalse();

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.blocks.reorder'), ['order' => [$blockId]])
        ->assertOk();

    $this->actingAs($talent, 'talent')
        ->deleteJson(route('talent.profile.blocks.destroy', $blockId))
        ->assertOk();
    expect(ProfileBlock::find($blockId))->toBeNull();
});

it('exposes the eligibility-filtered block picker for a skill tab', function () {
    $talent = editorTalent();
    $skillId = $talent->talentTypes->first()->id;

    $keys = collect($this->actingAs($talent, 'talent')->getJson(route('talent.profile.picker', ['talent_type_id' => $skillId]))->json('data'))
        ->pluck('key');

    expect($keys)->toContain('hero')->toContain('comp_card'); // universal + model-gated
    expect($keys)->not->toContain('equipment');               // crew-only

    // The universal (profile-level) section offers only universal blocks.
    $universalKeys = collect($this->actingAs($talent, 'talent')->getJson(route('talent.profile.picker'))->json('data'))->pluck('key');
    expect($universalKeys)->toContain('hero')->not->toContain('comp_card');
});

it('adds a block to a skill tab, then moves it to the universal section', function () {
    $talent = editorTalent();
    $skill = $talent->talentTypes->first();
    $gallery = BlockType::where('key', 'gallery')->firstOrFail(); // universal, repeatable

    $blockId = $this->actingAs($talent, 'talent')
        ->postJson(route('talent.profile.blocks.store'), ['block_type_id' => $gallery->id, 'talent_type_id' => $skill->id])
        ->assertCreated()->json('data.id');
    expect(ProfileBlock::find($blockId)->talent_type_id)->toBe($skill->id);

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.blocks.move', $blockId), ['talent_type_id' => null])
        ->assertOk()->assertJsonPath('data.talent_type_id', null);
    expect(ProfileBlock::find($blockId)->talent_type_id)->toBeNull();

    // Reorder within the universal scope.
    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.blocks.reorder'), ['talent_type_id' => null, 'order' => [$blockId]])
        ->assertOk();
});

it('rejects moving a block into a scope that is not one of the talent’s skills (422)', function () {
    $talent = editorTalent(); // model
    $skill = $talent->talentTypes->first();
    $gallery = BlockType::where('key', 'gallery')->firstOrFail();

    $blockId = $this->actingAs($talent, 'talent')
        ->postJson(route('talent.profile.blocks.store'), ['block_type_id' => $gallery->id, 'talent_type_id' => $skill->id])
        ->assertCreated()->json('data.id');

    $foreign = TalentType::where('slug', 'photography')->firstOrFail(); // not linked to this talent

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.blocks.move', $blockId), ['talent_type_id' => $foreign->id])
        ->assertStatus(422)->assertJsonPath('success', false);
});

it('rejects adding an ineligible block with 422', function () {
    $talent = editorTalent(); // model category
    $equipment = BlockType::where('key', 'equipment')->firstOrFail(); // by_category crew

    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.profile.blocks.store'), ['block_type_id' => $equipment->id])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('forbids editing another talent’s block', function () {
    $owner = editorTalent();
    $gallery = BlockType::where('key', 'gallery')->firstOrFail();
    $block = $owner->profileBlocks()->create([
        'block_type_id' => $gallery->id, 'title' => ['en' => 'x'], 'position' => 0,
        'is_visible' => true, 'status' => 'visible', 'settings' => [],
    ]);
    $intruder = Talent::factory()->create();

    $this->actingAs($intruder, 'talent')
        ->patchJson(route('talent.profile.blocks.visibility', $block->id), ['is_visible' => false])
        ->assertForbidden();

    $this->actingAs($intruder, 'talent')
        ->deleteJson(route('talent.profile.blocks.destroy', $block->id))
        ->assertForbidden();
});
