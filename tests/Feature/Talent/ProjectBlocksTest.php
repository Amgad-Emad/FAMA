<?php

use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\SkillsService;
use App\Support\ProjectBlocks;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Free-form project blocks (ADR-AN) — the prototype's WYSIWYG project editor:
 * the story is an ordered list of blocks; pictures are the project's own media;
 * summary/body are derived; the public page lays the blocks out by the
 * prototype's rules (chapters, Cases gallery, spreads, looks, before/after…).
 */
beforeEach(function () {
    Storage::fake('public');
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
    $this->talent = Talent::factory()->create(['is_published' => true, 'is_verified' => true]);
    app(SkillsService::class)->addType($this->talent, TalentType::where('slug', 'photography')->firstOrFail());
    $this->skill = $this->talent->talentTypes()->first()->id;

    $this->make = fn (array $blocks = []) => $this->actingAs($this->talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'Desert Light'], 'skill_ids' => [$this->skill], 'blocks' => $blocks,
    ])->assertCreated()->json('data.id');

    $this->upload = fn (int $id, int $n = 1, bool $place = false) => $this->actingAs($this->talent, 'talent')->post(
        route('talent.projects.media.store', $id),
        ['files' => array_map(fn ($k) => UploadedFile::fake()->image("p{$k}.jpg", 400, 300), range(1, $n)), 'place' => $place ? '1' : '0'],
        ['Accept' => 'application/json'],
    )->assertOk()->json('meta.added');

    $this->save = fn (int $id, array $blocks, array $extra = []) => $this->actingAs($this->talent, 'talent')->patchJson(route('talent.projects.update', $id), [
        'title' => ['en' => 'Desert Light'], 'skill_ids' => [$this->skill], 'blocks' => $blocks, ...$extra,
    ]);
});

it('stores the story as blocks and derives the summary and body from them', function () {
    $id = ($this->make)([
        ['t' => 'text', 'v' => '  A resort capsule shot at golden hour.  '],
        ['t' => 'heading', 'v' => 'The brief'],
        ['t' => 'text', 'v' => 'Linen carried the story.'],
        ['t' => 'quote', 'v' => 'The light did half the work.', 'by' => 'Omar'],
    ]);

    $project = Project::find($id);
    expect($project->blocks)->toHaveCount(4)
        ->and($project->blocks[0])->toBe(['t' => 'text', 'v' => 'A resort capsule shot at golden hour.'])
        ->and($project->getTranslation('summary', 'en'))->toBe('<p>A resort capsule shot at golden hour.</p>')
        ->and($project->getTranslation('body', 'en'))->toContain('<strong>The brief</strong>')
        ->and($project->getTranslation('body', 'en'))->toContain('Linen carried the story.')
        ->and($project->getTranslation('body', 'en'))->not->toContain('golden hour');

    // an unknown block type is a validation error, never silently stored
    $this->actingAs($this->talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'X'], 'skill_ids' => [$this->skill], 'blocks' => [['t' => 'nonsense']],
    ])->assertStatus(422)->assertJsonValidationErrors('blocks.0.t');
});

it('uses the first paragraph as the summary when the story opens with a chapter', function () {
    $id = ($this->make)([['t' => 'heading', 'v' => 'The brief'], ['t' => 'text', 'v' => 'Linen carried it.']]);

    expect(Project::find($id)->getTranslation('summary', 'en'))->toBe('<p>Linen carried it.</p>');
});

it('only lets a block show pictures the project owns', function () {
    $id = ($this->make)();
    [$own] = ($this->upload)($id);
    $other = ($this->make)();
    [$theirs] = ($this->upload)($other);

    ($this->save)($id, [['t' => 'image', 'm' => $own, 'cap' => 'Mine']])->assertOk();
    ($this->save)($id, [['t' => 'image', 'm' => $theirs]])->assertStatus(422);
    // a new project owns nothing yet, so its blocks can point at nothing
    $this->actingAs($this->talent, 'talent')->postJson(route('talent.projects.store'), [
        'title' => ['en' => 'X'], 'skill_ids' => [$this->skill], 'blocks' => [['t' => 'image', 'm' => $own]],
    ])->assertStatus(422);
});

it('accepts only YouTube or Vimeo for a video block, and http links for a social post', function () {
    $id = ($this->make)();

    ($this->save)($id, [['t' => 'video', 'url' => 'https://vimeo.com/123456']])->assertOk();
    ($this->save)($id, [['t' => 'video', 'url' => 'https://evil.example/clip']])->assertStatus(422);
    ($this->save)($id, [['t' => 'embed', 'url' => 'javascript:alert(1)']])->assertStatus(422);
});

it('keeps one Results and one Credits marker, clamps look pins and caps a look at eight pieces', function () {
    $id = ($this->make)();
    [$m] = ($this->upload)($id);
    $pieces = array_fill(0, 9, ['n' => 'Coat', 'd' => '', 'x' => 150, 'y' => -4]);

    ($this->save)($id, [['t' => 'results'], ['t' => 'results'], ['t' => 'credits'], ['t' => 'credits'], ['t' => 'look', 'm' => $m, 'items' => array_slice($pieces, 0, 8)]])->assertOk();
    $blocks = Project::find($id)->blocks;

    expect(collect($blocks)->pluck('t')->all())->toBe(['results', 'credits', 'look'])
        ->and($blocks[2]['items'][0])->toMatchArray(['x' => 97, 'y' => 3]);

    ($this->save)($id, [['t' => 'look', 'm' => $m, 'items' => $pieces]])->assertOk();
    expect(Project::find($id)->blocks[0]['items'])->toHaveCount(8);
});

it('uploads without placing for the editor, and places into Photos for everyone else', function () {
    $id = ($this->make)([['t' => 'text', 'v' => 'Hi']]);

    $added = ($this->upload)($id, 2, false);
    expect($added)->toHaveCount(2)
        ->and(collect(Project::find($id)->blocks)->pluck('t')->all())->toBe(['text']);

    ($this->upload)($id, 1, true);
    $blocks = Project::find($id)->blocks;
    expect($blocks[1]['t'])->toBe('gallery')->and($blocks[1]['items'])->toHaveCount(1);
});

it('orders the media by the story and keeps a removed picture for a grace period before deleting it', function () {
    $id = ($this->make)();
    [$a, $b] = ($this->upload)($id, 2);

    ($this->save)($id, [['t' => 'image', 'm' => $b], ['t' => 'gallery', 'items' => [['m' => $a]]]])->assertOk();
    $project = Project::with('media')->find($id);
    expect($project->orderedMedia()->pluck('id')->all())->toBe([$b, $a])
        ->and($project->lead_image_url)->toContain('p2');

    // b leaves the story: hidden at once (marked), file kept for Undo
    ($this->save)($id, [['t' => 'gallery', 'items' => [['m' => $a]]]])->assertOk();
    $project = Project::with('media')->find($id);
    expect($project->orderedMedia()->pluck('id')->all())->toBe([$a])
        ->and($project->allMedia()->pluck('id')->all())->toContain($b);

    // Undo inside the window brings it back
    ($this->save)($id, [['t' => 'image', 'm' => $b], ['t' => 'gallery', 'items' => [['m' => $a]]]])->assertOk();
    expect(Project::with('media')->find($id)->orderedMedia()->pluck('id')->all())->toBe([$b, $a]);

    // gone for good once the window has passed
    ($this->save)($id, [['t' => 'gallery', 'items' => [['m' => $a]]]])->assertOk();
    $this->travel(25)->hours();
    ($this->save)($id, [['t' => 'gallery', 'items' => [['m' => $a]]]])->assertOk();
    expect(Project::with('media')->find($id)->allMedia()->pluck('id')->all())->toBe([$a]);
});

it('lays a story with chapters out as the prototype does', function () {
    $id = ($this->make)();
    $m = ($this->upload)($id, 8);
    ($this->save)($id, [
        ['t' => 'text', 'v' => 'The opening line.'],
        ['t' => 'heading', 'v' => 'On set'],
        ['t' => 'text', 'v' => 'Two evenings, forty-minute windows.'],
        ['t' => 'gallery', 'items' => array_map(fn ($x) => ['m' => $x, 'cap' => "Frame {$x}"], array_slice($m, 0, 4))],
        ['t' => 'heading', 'v' => 'The palette'],
        ['t' => 'image', 'm' => $m[4], 'cap' => 'Swatches'],
        ['t' => 'text', 'v' => 'Sand and bone.'],
        ['t' => 'quote', 'v' => 'Stay out of its way.', 'by' => 'Omar Saleh'],
        ['t' => 'look', 'm' => $m[5], 'title' => 'Look 04', 'items' => [['n' => 'Overshirt', 'd' => 'Maison Nour · linen', 'x' => 20, 'y' => 30]]],
        ['t' => 'beforeafter', 'mb' => $m[6], 'ma' => $m[7], 'lb' => 'Raw', 'la' => 'Graded', 'cap' => 'Warmer sand'],
        ['t' => 'video', 'url' => 'https://www.youtube.com/watch?v=abcdefghijk', 'cap' => 'Behind the scenes'],
        ['t' => 'embed', 'url' => 'https://www.instagram.com/reel/maisonnour'],
        ['t' => 'embed', 'url' => 'https://www.tiktok.com/@demo.talent/video/1'],
        ['t' => 'results'],
        ['t' => 'credits'],
    ], ['results' => [['label' => 'Reach', 'value' => '1.2M']]])->assertOk();

    $html = $this->get(route('talent.work', ['slug' => $this->talent->slug, 'project' => $id]))->assertOk()->getContent();

    expect($html)->toContain('<main class="ed" id="page"')        // the long page, not is-short
        ->toContain('<p class="lead">The opening line.</p>')
        ->toContain('class="colo"')
        ->toContain('data-cases')                                     // heading + line + 4 photos = Cases
        ->toContain('<h2 class="cs-title">On set</h2>')
        ->toContain('Two evenings, forty-minute windows.')
        ->toContain('CHAPTER 02')
        ->toContain('<div class="spread">')                           // image then text, side by side
        ->toContain('<blockquote class="quote">Stay out of its way.<cite>Omar Saleh</cite>')
        ->toContain('class="look"')->toContain('left:20%;top:30%')
        ->toContain('class="ba"')->toContain('Raw / Graded')
        ->toContain('https://www.youtube-nocookie.com/embed/abcdefghijk')
        ->toContain('style="--n:2"')                                  // two posts, one row
        ->toContain('class="res"')->toContain('1.2M')
        ->not->toContain('class="cred"');                             // credits live in "Made by"
});

it('lays a story without chapters out as the short page', function () {
    $id = ($this->make)();
    [$a, $b] = ($this->upload)($id, 2);
    ($this->save)($id, [
        ['t' => 'text', 'v' => 'Short and sweet.'],
        ['t' => 'gallery', 'items' => [['m' => $a], ['m' => $b]]],
        ['t' => 'text', 'v' => 'And one more thing.'],
    ])->assertOk();

    $html = $this->get(route('talent.work', ['slug' => $this->talent->slug, 'project' => $id]))->assertOk()->getContent();

    expect($html)->toContain('ed is-short')
        ->toContain('<div class="s-intro">')->toContain('<p class="lead">Short and sweet.</p>')
        ->toContain('<div class="sgrid">')
        ->toContain('<p class="txt">And one more thing.</p>');
});

it('keeps a confidential client off the page', function () {
    $id = ($this->make)();
    [$m] = ($this->upload)($id);
    ($this->save)($id, [
        ['t' => 'look', 'm' => $m, 'items' => [['n' => 'Coat', 'd' => 'Maison Nour · wool', 'x' => 50, 'y' => 50]]],
    ], ['client_name' => 'Maison Nour', 'client_confidential' => true, 'url' => 'https://maisonnour.com'])->assertOk();

    $html = $this->get(route('talent.work', ['slug' => $this->talent->slug, 'project' => $id]))->assertOk()->getContent();

    expect($html)->toContain('Confidential client')
        ->toContain('The client asked not to be named')
        ->not->toContain('Maison Nour')
        ->not->toContain('maisonnour.com');
});

it('reads a project written before blocks as blocks', function () {
    $project = Project::factory()->for($this->talent)->create([
        'talent_type_id' => $this->skill,
        'summary' => ['en' => '<p>The opening line.</p>'],
        'body' => ['en' => '<p><strong>The brief</strong></p><p>First paragraph.</p><ul><li>One</li></ul>'],
        'results' => [['label' => 'Reach', 'value' => '1M']],
    ]);
    $project->update(['blocks' => null]);

    expect(ProjectBlocks::fromLegacy($project->fresh(['media', 'credits'])))->toBe([
        ['t' => 'text', 'v' => 'The opening line.'],
        ['t' => 'heading', 'v' => 'The brief'],
        ['t' => 'text', 'v' => 'First paragraph.'],
        ['t' => 'text', 'v' => '• One'],
        ['t' => 'results'],
    ]);
});

it('opens the editor on the stored blocks', function () {
    $id = ($this->make)([['t' => 'heading', 'v' => 'Chapter one']]);

    $this->actingAs($this->talent, 'talent')->get(route('talent.projects.edit', $id))
        ->assertOk()
        ->assertSee('famaProjectEdit', false)
        ->assertSee('Chapter one')
        ->assertSee('Look with tags');
});
