<?php

use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('scopes a project to a skill — defaults to the primary skill, honours an explicit one, rejects a foreign one', function () {
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
    $talent = Talent::factory()->create();
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();
    $talent->talentTypes()->attach([
        $model->id => ['is_primary' => true, 'position' => 0],
        $photographer->id => ['is_primary' => false, 'position' => 1],
    ]);

    // No skill given → defaults to the primary skill.
    $id = $this->actingAs($talent, 'talent')
        ->postJson(route('talent.content.store', ['type' => 'projects']), ['title' => ['en' => 'Campaign']])
        ->assertCreated()->json('data.id');
    expect(Project::find($id)->talent_type_id)->toBe($model->id);

    // Explicit skill honoured.
    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.content.store', ['type' => 'projects']), ['title' => ['en' => 'Shoot'], 'talent_type_id' => $photographer->id])
        ->assertCreated()->assertJsonPath('data.talent_type_id', $photographer->id);

    // A skill the talent does not have → 422.
    $foreign = TalentType::where('slug', 'styling')->firstOrFail();
    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.content.store', ['type' => 'projects']), ['title' => ['en' => 'X'], 'talent_type_id' => $foreign->id])
        ->assertStatus(422);
});

it('publishes and unpublishes from the profile editor', function () {
    $talent = Talent::factory()->draft()->create(['display_name' => 'Pub']);

    $this->actingAs($talent, 'talent')->patchJson(route('talent.profile.publish'), ['publish' => true])
        ->assertOk()->assertJsonPath('data.is_published', true);
    expect($talent->fresh()->is_published)->toBeTrue();

    $this->actingAs($talent, 'talent')->patchJson(route('talent.profile.publish'), ['publish' => false])->assertOk();
    expect($talent->fresh()->is_published)->toBeFalse();
});

it('refuses to publish a profile with no display name (422)', function () {
    $talent = Talent::factory()->draft()->create(['display_name' => null]);

    $this->actingAs($talent, 'talent')->patchJson(route('talent.profile.publish'), ['publish' => true])
        ->assertStatus(422)->assertJsonPath('success', false);
});

it('updates the username (slug) from the profile editor', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->patchJson(route('talent.profile.update'), ['slug' => 'my-new-username'])
        ->assertOk()->assertJsonPath('data.slug', 'my-new-username');
    expect($talent->fresh()->slug)->toBe('my-new-username');
});

it('rejects a taken username with a "username" validation message', function () {
    Talent::factory()->create(['slug' => 'taken-name']);
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->patchJson(route('talent.profile.update'), ['slug' => 'taken-name'])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('errors.slug.0', 'The username has already been taken.');
});

it('sets, reads back and clears the pricing rate (all-or-nothing)', function () {
    $talent = Talent::factory()->create();

    // Complete rate — currency upper-cased by the service.
    $this->actingAs($talent, 'talent')->patchJson(route('talent.profile.pricing'), [
        'rate_unit' => 'day', 'rate_amount' => 1500, 'rate_currency' => 'egp',
    ])->assertOk()
        ->assertJsonPath('data.rate_unit', 'day')
        ->assertJsonPath('data.rate_amount', '1500.00')
        ->assertJsonPath('data.rate_currency', 'EGP');

    // Blank amount clears the whole rate.
    $this->actingAs($talent, 'talent')->patchJson(route('talent.profile.pricing'), [
        'rate_unit' => '', 'rate_amount' => '', 'rate_currency' => '',
    ])->assertOk()->assertJsonPath('data.rate_amount', null);

    $fresh = $talent->fresh();
    expect($fresh->rate_unit)->toBeNull();
    expect($fresh->rate_currency)->toBeNull();
});

it('rejects a partial pricing rate (422, all-or-nothing)', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->patchJson(route('talent.profile.pricing'), ['rate_amount' => 1000])
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

it('manages a gallery content editor: create, list, reorder, remove', function () {
    $talent = Talent::factory()->create();

    $id = $this->actingAs($talent, 'talent')
        ->postJson(route('talent.content.store', ['type' => 'gallery']), ['media_type' => 'image', 'caption' => ['en' => 'Shot 1']])
        ->assertCreated()->json('data.id');

    $this->actingAs($talent, 'talent')->getJson(route('talent.content.data', ['type' => 'gallery']))
        ->assertOk()->assertJsonPath('meta.pagination.total', 1);

    $this->actingAs($talent, 'talent')->patchJson(route('talent.content.reorder', ['type' => 'gallery']), ['order' => [$id]])->assertOk();

    $this->actingAs($talent, 'talent')->deleteJson(route('talent.content.destroy', ['type' => 'gallery', 'id' => $id]))->assertOk();
    expect(PortfolioItem::find($id))->toBeNull();
});

it('creates a gallery item from the drop-zone blank payload (null position) and appends', function () {
    $talent = Talent::factory()->create();

    // The exact payload the upload drop-zone / add form sends from the blank.
    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.content.store', ['type' => 'gallery']), ['position' => null, 'caption' => ['en' => '', 'ar' => ''], 'media_type' => 'image', 'embed_url' => ''])
        ->assertCreated();
    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.content.store', ['type' => 'gallery']), ['position' => null, 'media_type' => 'image'])
        ->assertCreated();

    expect(PortfolioItem::where('talent_id', $talent->id)->orderBy('position')->pluck('position')->all())->toBe([0, 1]);
});

it('uploads media to a content item through the media library', function () {
    Storage::fake('public');
    $talent = Talent::factory()->create();

    $id = $this->actingAs($talent, 'talent')
        ->postJson(route('talent.content.store', ['type' => 'gallery']), ['media_type' => 'image'])
        ->assertCreated()->json('data.id');

    $this->actingAs($talent, 'talent')
        ->post(route('talent.content.media', ['type' => 'gallery', 'id' => $id]), ['file' => UploadedFile::fake()->image('shot.jpg', 400, 500)])
        ->assertOk();

    expect(PortfolioItem::find($id)->getMedia('gallery'))->toHaveCount(1);
});

it('404s for an unknown content type', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->getJson(route('talent.content.data', ['type' => 'nope']))->assertNotFound();
});

it('forbids managing another talent’s content item', function () {
    $owner = Talent::factory()->create();
    $item = PortfolioItem::factory()->for($owner)->create();
    $intruder = Talent::factory()->create();

    $this->actingAs($intruder, 'talent')
        ->deleteJson(route('talent.content.destroy', ['type' => 'gallery', 'id' => $item->id]))
        ->assertForbidden();
});
