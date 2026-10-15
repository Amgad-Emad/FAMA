<?php

use App\Models\BlockType;
use App\Models\PortfolioItem;
use App\Models\Project;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\SkillsService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]));

it('adds a skill, seeds its OWN tab’s blocks and makes the first one primary', function () {
    $talent = Talent::factory()->create(['status' => 'created', 'display_name' => 'P']);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();

    app(SkillsService::class)->addType($talent, $model);
    $talent->refresh()->load('talentTypes');

    expect($talent->talentTypes)->toHaveCount(1);
    expect((bool) $talent->talentTypes->first()->pivot->is_primary)->toBeTrue();
    // The skill's own blocks land in its tab; the profile-level defaults
    // (ADR-Z) land in the universal region, exactly once each. Derived from the
    // catalog rather than hardcoded, so flagging another block cannot silently
    // break this test's meaning.
    $profileDefaults = BlockType::where('is_profile_default', true)->count();

    expect($talent->profileBlocks()->count())->toBeGreaterThan(0);
    expect($talent->profileBlocks()->whereNull('talent_type_id')->count())->toBe($profileDefaults);
    expect($talent->profileBlocks()->where('talent_type_id', $model->id)->count())
        ->toBe($talent->profileBlocks()->count() - $profileDefaults);
    expect($talent->status->getValue())->toBe('draft'); // Created → Draft after seeding
});

it('adding a second skill creates that skill’s OWN blocks (incl. a second gallery) without cross-skill dedupe', function () {
    $talent = Talent::factory()->create(['status' => 'created', 'display_name' => 'P']);
    $service = app(SkillsService::class);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();

    $service->addType($talent, $model);
    $service->addType($talent->fresh(), $photographer);
    $talent->refresh();

    expect($talent->talentTypes()->count())->toBe(2);

    // Gallery (universal, in both default lists) appears in BOTH tabs.
    $galleries = $talent->profileBlocks()->whereRelation('blockType', 'key', 'gallery')->get();
    expect($galleries)->toHaveCount(2);
    expect($galleries->pluck('talent_type_id')->sort()->values()->all())->toBe(collect([$model->id, $photographer->id])->sort()->values()->all());

    // Within a single tab, no duplicate block_type.
    $modelIds = $talent->profileBlocks()->where('talent_type_id', $model->id)->pluck('block_type_id');
    expect($modelIds)->toHaveCount($modelIds->unique()->count());

    expect($talent->talentTypes()->wherePivot('is_primary', true)->first()->slug)->toBe('modeling');
});

it('blocks duplicate skills', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $service = app(SkillsService::class);
    $service->addType($talent, $model);

    expect(fn () => $service->addType($talent->fresh(), $model))->toThrow(InvalidArgumentException::class);
});

it('derives the primary flag from the order (first skill only) on reorder, add and remove', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $service = app(SkillsService::class);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();
    $stylist = TalentType::where('slug', 'styling')->firstOrFail();
    $primary = fn () => $talent->talentTypes()->wherePivot('is_primary', true)->pluck('slug')->all();
    $order = fn () => $talent->fresh()->talentTypes->pluck('slug')->all();

    // Add: appended at the end; the first skill stays the one flagged.
    $service->addType($talent, $model);
    $service->addType($talent->fresh(), $photographer);
    expect($order())->toBe(['modeling', 'photography'])->and($primary())->toBe(['modeling']);

    // Reorder: the flag follows position 0, and only one skill carries it.
    $service->reorderTypes($talent->fresh(), [$photographer->id, $model->id]);
    expect($order())->toBe(['photography', 'modeling'])->and($primary())->toBe(['photography']);

    // "Make primary" (route kept) = move to the front.
    $service->setPrimary($talent->fresh(), $model);
    expect($order())->toBe(['modeling', 'photography'])->and($primary())->toBe(['modeling']);

    // Add first: the new skill leads and takes the flag.
    $service->addType($talent->fresh(), $stylist, true);
    expect($order()[0])->toBe('styling')->and($primary())->toBe(['styling']);

    // Remove the first: the next in order opens first now.
    $service->removeType($talent->fresh(), $stylist);
    expect($order())->toBe(['modeling', 'photography'])->and($primary())->toBe(['modeling']);
});

it('removes a skill: deletes its tab blocks but PRESERVES content (items un-linked, projects un-scoped)', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $service = app(SkillsService::class);
    $service->addType($talent, $model);
    $talent->refresh();

    // Attach a gallery item to the model tab's gallery block, and a project to the skill.
    $gallery = $talent->profileBlocks()->whereRelation('blockType', 'key', 'gallery')->where('talent_type_id', $model->id)->firstOrFail();
    $item = PortfolioItem::factory()->for($talent)->create(['block_id' => $gallery->id]);
    $project = Project::factory()->for($talent)->create(['talent_type_id' => $model->id]);

    $service->removeType($talent->fresh(), $model);

    expect($talent->talentTypes()->count())->toBe(0);
    // The skill's blocks are gone…
    expect($talent->profileBlocks()->where('talent_type_id', $model->id)->count())->toBe(0);
    // …but the content survives (un-linked / un-scoped).
    expect($item->fresh())->not->toBeNull();
    expect($item->fresh()->block_id)->toBeNull();
    expect($project->fresh())->not->toBeNull();
    expect($project->fresh()->talent_type_id)->toBeNull();
});
