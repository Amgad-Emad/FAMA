<?php

use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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
        ->assertSee(__('Add skill'))
        ->assertSee(__('Username'))
        ->assertSee(__('Core details'))
        ->assertSee(__('Pricing rate'))
        // ADR-N retired the "Professions" label in favour of "Skills" — the old one
        // must not survive anywhere in the consolidated editor.
        ->assertDontSee('Professions');
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

it('renders the profile-image uploader in the editor', function () {
    $talent = editorTalent();

    $this->actingAs($talent, 'talent')->withoutVite()->get(route('talent.profile.edit'))
        ->assertOk()
        ->assertSee(__('Profile photo'))
        ->assertSee(__('Upload photo'))
        ->assertSee('uploadAvatar($event.target.files)', false);
});

it('uploads a profile image and returns its URL', function () {
    Storage::fake('public');
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')
        ->post(route('talent.profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('me.jpg', 600, 600)])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.avatar_url', fn ($url) => is_string($url) && $url !== '');

    expect($talent->fresh()->getFirstMedia('avatar'))->not->toBeNull();
    expect($talent->fresh()->avatar_url)->not->toBeNull();
});

it('replaces the avatar on re-upload (single-file collection)', function () {
    Storage::fake('public');
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')
        ->post(route('talent.profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('first.jpg')])
        ->assertOk();
    $this->actingAs($talent, 'talent')
        ->post(route('talent.profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('second.jpg')])
        ->assertOk();

    expect($talent->fresh()->getMedia('avatar'))->toHaveCount(1); // not accumulated
});

it('removes the profile image (falls back to no URL)', function () {
    Storage::fake('public');
    $talent = Talent::factory()->create();
    $talent->addMedia(UploadedFile::fake()->image('me.jpg'))->toMediaCollection('avatar');

    $this->actingAs($talent, 'talent')
        ->deleteJson(route('talent.profile.avatar.destroy'))
        ->assertOk()
        ->assertJsonPath('data.avatar_url', null);

    expect($talent->fresh()->getFirstMedia('avatar'))->toBeNull();
});

it('rejects a non-image or oversized avatar (422)', function () {
    Storage::fake('public');
    $talent = Talent::factory()->create();

    // Not an image.
    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.profile.avatar.update'), ['avatar' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf')])
        ->assertStatus(422)
        ->assertJsonValidationErrors('avatar');

    // Too large (> 5 MB).
    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('huge.jpg')->size(6000)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('avatar');

    expect($talent->fresh()->getFirstMedia('avatar'))->toBeNull();
});

it('requires authentication to change the avatar', function () {
    Storage::fake('public');

    $this->post(route('talent.profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('me.jpg')])
        ->assertRedirect(); // guest → guard redirect, never reaches the controller
});

it('adds an eligible block, fills, toggles, reorders and removes it', function () {
    $talent = editorTalent();
    $gallery = BlockType::where('key', 'gallery')->firstOrFail();

    $blockId = $this->actingAs($talent, 'talent')
        ->postJson(route('talent.profile.blocks.store'), ['block_type_id' => $gallery->id])
        ->assertCreated()
        ->json('data.id');

    // Block names are admin-governed: a talent-submitted title is silently ignored.
    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.blocks.update', $blockId), ['title' => ['en' => 'My work'], 'content' => ['x' => 1]])
        ->assertOk();
    $filled = ProfileBlock::find($blockId);
    expect($filled->getTranslation('title', 'en'))->toBe('Gallery')
        ->and($filled->content)->toBe(['x' => 1]);

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.blocks.visibility', $blockId), ['is_visible' => false])
        ->assertOk();
    expect(ProfileBlock::find($blockId)->is_visible)->toBeFalse();

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

    expect($keys)->toContain('brand_collabs')->toContain('comp_card'); // universal + model-gated
    expect($keys)->not->toContain('equipment');                        // crew-only
    expect($keys)->not->toContain('hero');                             // retired: inactive (ADR-O)

    // The universal (profile-level) section offers only universal blocks.
    $universalKeys = collect($this->actingAs($talent, 'talent')->getJson(route('talent.profile.picker'))->json('data'))->pluck('key');
    expect($universalKeys)->toContain('brand_collabs')->not->toContain('comp_card');
});

it('no longer exposes the block reorder / move endpoints (Sections panel removed)', function () {
    $talent = editorTalent();
    $block = ProfileBlock::factory()->create(['talent_id' => $talent->id]);

    expect(Route::has('talent.profile.blocks.reorder'))->toBeFalse()
        ->and(Route::has('talent.profile.blocks.move'))->toBeFalse();

    // The old URLs no longer move anything: `reorder` falls through to the
    // {block} update route (a non-numeric id → 404) and `move` matches nothing.
    $this->actingAs($talent, 'talent')
        ->patchJson('/talent/profile/blocks/'.$block->id.'/move', ['talent_type_id' => null])
        ->assertNotFound();
    expect($block->fresh()->talent_type_id)->toBe($block->talent_type_id);
});

it('does not render the Sections panel on Edit profile', function () {
    $this->actingAs(editorTalent(), 'talent')
        ->get(route('talent.profile.edit'))
        ->assertOk()
        ->assertDontSee('id="sections"', false)
        ->assertDontSee('Hidden sections keep their content.');
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

it('offers one primary skill instead of reordering (13 Nov 2026)', function () {
    $talent = editorTalent();

    $this->actingAs($talent, 'talent')->withoutVite()->get(route('talent.profile.edit'))
        ->assertOk()
        ->assertSee('makePrimary(s)', false)
        ->assertSee(__('Make primary'))
        ->assertSee(__('Each skill is a tab on your profile. Your primary skill opens first.'))
        ->assertDontSee('data-grip', false)
        ->assertDontSee('skStart(', false)
        ->assertDontSee('data-mv=', false);
});
