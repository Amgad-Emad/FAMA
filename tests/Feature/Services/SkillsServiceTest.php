<?php

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
    // All seeded blocks are stamped with the skill (its tab).
    expect($talent->profileBlocks()->count())->toBeGreaterThan(0);
    expect($talent->profileBlocks()->whereNull('talent_type_id')->count())->toBe(0);
    expect($talent->profileBlocks()->where('talent_type_id', $model->id)->count())->toBe($talent->profileBlocks()->count());
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

it('reassigns the primary and reorders skills', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $service = app(SkillsService::class);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();

    $service->addType($talent, $model);
    $service->addType($talent->fresh(), $photographer);

    $service->setPrimary($talent->fresh(), $photographer);
    expect($talent->talentTypes()->wherePivot('is_primary', true)->first()->slug)->toBe('photography');
    expect($talent->talentTypes()->wherePivot('is_primary', true)->count())->toBe(1);

    $service->reorderTypes($talent->fresh(), [$photographer->id, $model->id]);
    $position = (int) $talent->talentTypes()->where('talent_types.id', $photographer->id)->first()->pivot->position;
    expect($position)->toBe(0);
});

it('removes a skill: deletes its tab blocks but PRESERVES content (items un-linked, projects un-scoped)', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $service = app(SkillsService::class);
    $service->addType($talent, $model);
    $talent->refresh();

    // Attach a gallery item to the model tab's gallery block, and a project to the skill.
    $gallery = $talent->profileBlocks()->whereRelation('blockType', 'key', 'gallery')->where('talent_type_id', $model->id)->firstOrFail();
    $item = \App\Models\PortfolioItem::factory()->for($talent)->create(['block_id' => $gallery->id]);
    $project = \App\Models\Project::factory()->for($talent)->create(['talent_type_id' => $model->id]);

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
