<?php

use App\Models\BlockType;
use App\Models\PortfolioItem;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('retires the generic projects content editor — the type 404s and the page redirects to the Projects page', function () {
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
    $talent = Talent::factory()->create();
    $talent->talentTypes()->attach(TalentType::where('slug', 'modeling')->firstOrFail()->id, ['is_primary' => true, 'position' => 0]);

    // Projects are authored on their own page (ADR-X) — the registry no longer
    // serves them, so the generic CRUD endpoint is gone.
    $this->actingAs($talent, 'talent')
        ->postJson(route('talent.content.store', ['type' => 'projects']), ['title' => ['en' => 'Campaign']])
        ->assertNotFound();

    // A stale link into the content page lands on the Projects page instead.
    $this->actingAs($talent, 'talent')
        ->get(route('talent.content', ['section' => 'projects']))
        ->assertRedirect(route('talent.projects'));
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

// ---------------------------------------------------------------------------
// The profile-content page is SECTION-based: pick a section, get all of its
// content blocks stacked (ADR-Q/ADR-R ordering).
// ---------------------------------------------------------------------------

/** A talent with two skills and one block per scope, blocks seeded per skill. */
function sectionedTalent(): Talent
{
    $talent = Talent::factory()->create();
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();
    $talent->talentTypes()->attach([
        $model->id => ['is_primary' => true, 'position' => 0],
        $photographer->id => ['is_primary' => false, 'position' => 1],
    ]);

    $block = fn (string $key, ?int $scope) => $talent->profileBlocks()->create([
        'block_type_id' => BlockType::where('key', $key)->firstOrFail()->id,
        'talent_type_id' => $scope,
        'position' => 0,
        'is_visible' => true,
        'status' => 'visible',
    ]);

    $block('brand_collabs', null);
    $block('reviews', null);
    $block('look_types', $model->id);
    $block('showreel', $photographer->id);

    return $talent;
}

it('renders one section at a time with every content block in it stacked', function () {
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
    $talent = sectionedTalent();

    // Universal: the brand-collabs editor AND the reviews list, both on the page.
    $page = $this->actingAs($talent, 'talent')->get(route('talent.content', ['section' => 'universal']))->assertOk();
    $page->assertSee('id="block-brand_collabs"', false)
        ->assertSee('id="block-reviews"', false)
        ->assertDontSee('id="block-look_types"', false);

    // A skill section shows only its own blocks.
    $this->actingAs($talent, 'talent')->get(route('talent.content', ['section' => 'modeling']))
        ->assertOk()
        ->assertSee('id="block-look_types"', false)
        ->assertDontSee('id="block-showreel"', false);

    // Every section is offered as a button, with its block count.
    $page->assertSee(route('talent.content', ['section' => 'photography']), false);
});

it('resolves a legacy content-type url to the section that shows that block, and 404s on an unknown one', function () {
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
    $talent = sectionedTalent();

    $this->actingAs($talent, 'talent')->get('/talent/content/showreel')
        ->assertOk()
        ->assertSee('id="block-showreel"', false);

    $this->actingAs($talent, 'talent')->get('/talent/content/nope')->assertNotFound();
});

it('never offers an editor for a block type that has none (comp_card)', function () {
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
    $talent = sectionedTalent();
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $talent->profileBlocks()->create([
        'block_type_id' => BlockType::where('key', 'comp_card')->firstOrFail()->id,
        'talent_type_id' => $model->id,
        'position' => 1,
        'is_visible' => true,
        'status' => 'visible',
    ]);

    $this->actingAs($talent, 'talent')->get(route('talent.content', ['section' => 'modeling']))
        ->assertOk()
        ->assertDontSee('id="block-comp_card"', false);

    // The editor knows which keys are editable, so it hides the dead link.
    $this->actingAs($talent, 'talent')->get(route('talent.profile.edit'))
        ->assertOk()
        ->assertViewHas('contentKeys', fn (array $keys) => in_array('reviews', $keys, true) && ! in_array('comp_card', $keys, true));
});
