<?php

use App\Http\Resources\PublicProfileResource;
use App\Models\BlockType;
use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\Review;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
});

/** A block in a scope (talent_type_id NULL = universal). */
function scopedBlock(Talent $talent, string $key, ?int $typeId, int $position = 0, bool $visible = true): \App\Models\ProfileBlock
{
    $bt = BlockType::where('key', $key)->firstOrFail();

    return $talent->profileBlocks()->create([
        'block_type_id' => $bt->id, 'talent_type_id' => $typeId, 'title' => ['en' => ucfirst($key)],
        'position' => $position, 'is_visible' => $visible, 'status' => $visible ? 'visible' : 'hidden', 'settings' => [],
    ]);
}

/**
 * A published two-skill talent (model primary + photographer): a universal reviews
 * block, a gallery per tab (with a distinctly-captioned item), and a projects block
 * in the photographer tab. Projects are scoped per skill.
 */
function tabbedTalent(): Talent
{
    $talent = Talent::factory()->create(['slug' => 'multi', 'display_name' => 'Multi', 'is_published' => true]);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();
    $talent->talentTypes()->attach([
        $model->id => ['is_primary' => true, 'position' => 0],
        $photographer->id => ['is_primary' => false, 'position' => 1],
    ]);

    scopedBlock($talent, 'reviews', null);                            // universal
    $modelGallery = scopedBlock($talent, 'gallery', $model->id);      // model tab
    $photGallery = scopedBlock($talent, 'gallery', $photographer->id); // photographer tab
    scopedBlock($talent, 'projects', $photographer->id, 1);           // photographer tab

    PortfolioItem::factory()->for($talent)->create(['block_id' => $modelGallery->id, 'media_type' => 'image', 'caption' => ['en' => 'ModelShotAlpha'], 'position' => 0]);
    PortfolioItem::factory()->for($talent)->create(['block_id' => $photGallery->id, 'media_type' => 'image', 'caption' => ['en' => 'PhotoShotBravo'], 'position' => 0]);

    Project::factory()->for($talent)->create(['talent_type_id' => $photographer->id, 'title' => ['en' => 'PhotographerProjectX']]);
    Project::factory()->for($talent)->create(['talent_type_id' => $model->id, 'title' => ['en' => 'ModelProjectY']]);

    Review::factory()->for($talent)->create(['rating' => 5, 'body' => 'UniversalReviewBody']);

    return $talent;
}

it('renders identity + universal blocks + a tab per skill, with the primary tab active server-side', function () {
    tabbedTalent();

    $res = $this->get('/multi')->assertOk();

    // Identity + universal block (reviews) render at the top.
    $res->assertSee('Multi')->assertSee('@multi')->assertSee('UniversalReviewBody');
    // Both skills appear as tab buttons.
    $res->assertSee('Modeling')->assertSee('Photography');
    // The PRIMARY (model) tab is active by default → its content is server-rendered…
    $res->assertSee('ModelShotAlpha');
    // …and the photographer tab is lazy (NOT server-rendered).
    $res->assertDontSee('PhotoShotBravo');
});

it('renders a prominent, accessible tab bar with the primary tab active + a panel heading', function () {
    tabbedTalent();

    $res = $this->get('/multi')->assertOk();

    // ARIA tab pattern: a tablist of tabs controlling one tabpanel.
    $res->assertSee('role="tablist"', false)
        ->assertSee('role="tab"', false)
        ->assertSee('role="tabpanel"', false)
        ->assertSee('id="skill-tab-modeling"', false)
        ->assertSee('id="skill-tab-photography"', false)
        ->assertSee('id="skill-tabpanel"', false)
        ->assertSee('aria-controls="skill-tabpanel"', false)
        ->assertSee(':aria-selected="active ===', false)   // active state is bound, not a faint underline
        ->assertSee('aria-labelledby', false);             // panel labelled by the active tab
    // The panel shows the active skill's name as a heading (context on mobile scroll).
    $res->assertSee('x-text="labels[active]"', false);
});

it('wires keyboard arrow navigation across the tablist (roving tabindex)', function () {
    tabbedTalent();

    $this->get('/multi')
        ->assertOk()
        ->assertSee('x-ref="tablist"', false)              // the component reaches the tab buttons…
        ->assertSee('onTabKey($event', false)              // …on arrow/Home/End key presses
        ->assertSee(':tabindex="active ===', false);       // roving tabindex (only the active tab is tabbable)
});

it('no longer renders the header skill chips (the tab bar is the navigation)', function () {
    tabbedTalent();

    // The old chips activated tabs via jump(); nothing should call it anymore.
    $this->get('/multi')->assertOk()->assertDontSee('jump(', false);
});

it('lazily returns ONLY a skill’s own visible blocks (projects scoped to that skill)', function () {
    tabbedTalent();

    $html = $this->getJson(route('talent.tab', ['slug' => 'multi', 'skill' => 'photography']))
        ->assertOk()->assertJsonPath('success', true)->json('data.html');

    expect($html)->toContain('PhotoShotBravo');          // photographer gallery
    expect($html)->toContain('PhotographerProjectX');    // photographer projects only
    expect($html)->not->toContain('ModelShotAlpha');     // not the model tab
    expect($html)->not->toContain('ModelProjectY');      // not the model's project
});

it('opens the tab named by the ?skill= deep link (shareable + back-button)', function () {
    tabbedTalent();

    $res = $this->get('/multi?skill=photography')->assertOk();

    // The deep-linked (photographer) tab is now the server-rendered one…
    $res->assertSee('PhotoShotBravo')->assertSee('PhotographerProjectX');
    // …and the model tab becomes the lazy one.
    $res->assertDontSee('ModelShotAlpha');
});

it('links a tab’s Projects to the existing detail page /{slug}/work/{project}', function () {
    $talent = tabbedTalent();
    $project = Project::where('talent_id', $talent->id)->where('title->en', 'PhotographerProjectX')->firstOrFail();

    $html = $this->getJson(route('talent.tab', ['slug' => 'multi', 'skill' => 'photography']))->json('data.html');

    expect($html)->toContain(route('talent.work', ['slug' => 'multi', 'project' => $project->id]));
});

it('shows no tab bar when the talent has a single skill (renders that skill directly)', function () {
    $talent = Talent::factory()->create(['slug' => 'solo', 'display_name' => 'Solo', 'is_published' => true]);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $talent->talentTypes()->attach($model->id, ['is_primary' => true, 'position' => 0]);
    $gallery = scopedBlock($talent, 'gallery', $model->id);
    PortfolioItem::factory()->for($talent)->create(['block_id' => $gallery->id, 'media_type' => 'image', 'caption' => ['en' => 'SoloShot'], 'position' => 0]);

    $this->get('/solo')
        ->assertOk()
        ->assertSee('SoloShot')             // the single skill's blocks render directly…
        ->assertDontSee('role="tablist"', false); // …and there is no tab bar.
});

it('gives a skill with no VISIBLE blocks no tab (its lazy endpoint 404s)', function () {
    $talent = Talent::factory()->create(['slug' => 'mixed', 'display_name' => 'Mixed', 'is_published' => true]);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();
    $talent->talentTypes()->attach([
        $model->id => ['is_primary' => true, 'position' => 0],
        $photographer->id => ['is_primary' => false, 'position' => 1],
    ]);
    scopedBlock($talent, 'gallery', $model->id);                       // model: visible
    scopedBlock($talent, 'showreel', $photographer->id, 0, false);     // photographer: hidden only

    // The model tab is fetchable…
    $this->getJson(route('talent.tab', ['slug' => 'mixed', 'skill' => 'modeling']))->assertOk();
    // …the block-less (only-hidden) skill has no tab.
    $this->getJson(route('talent.tab', ['slug' => 'mixed', 'skill' => 'photography']))->assertNotFound();
});

it('404s the lazy tab for an unpublished or foreign profile / unknown skill', function () {
    tabbedTalent();

    $this->getJson(route('talent.tab', ['slug' => 'multi', 'skill' => 'styling']))->assertNotFound();   // not one of the talent's skills
    $this->getJson(route('talent.tab', ['slug' => 'nobody', 'skill' => 'modeling']))->assertNotFound();     // unknown profile

    Talent::factory()->draft()->create(['slug' => 'draft-one']);
    $this->getJson(route('talent.tab', ['slug' => 'draft-one', 'skill' => 'modeling']))->assertNotFound();  // unpublished
});

it('shapes the public-profile API resource: identity + universal_blocks + skills[].blocks[]', function () {
    tabbedTalent();
    $talent = Talent::with(['talentTypes', 'profileBlocks.blockType', 'projects', 'reviews' => fn ($q) => $q->approved()])
        ->where('slug', 'multi')->firstOrFail();

    $array = PublicProfileResource::make($talent)->toArray(request());

    expect($array)->toHaveKeys(['identity', 'universal_blocks', 'skills']);
    expect($array['identity'])->toHaveKeys(['slug', 'display_name', 'pricing_rate', 'primary_skill', 'skills']);
    expect($array['identity']['primary_skill'])->toBe('modeling');
    expect($array['universal_blocks'])->toHaveCount(1);                          // the reviews block

    $photographer = collect($array['skills'])->firstWhere('slug', 'photography');
    expect($photographer['blocks'])->toHaveCount(2);                            // gallery + projects
    $model = collect($array['skills'])->firstWhere('slug', 'modeling');
    expect($model['blocks'])->toHaveCount(1);                                   // gallery
});
