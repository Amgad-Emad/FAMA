<?php

use App\Actions\SeedProfileBlocks;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]));

it('seeds merged, de-duped default blocks and moves Created → Draft', function () {
    $talent = Talent::factory()->create(['status' => 'created', 'display_name' => 'Multi']);
    $model = TalentType::where('slug', 'model')->firstOrFail();
    $photographer = TalentType::where('slug', 'photographer')->firstOrFail();
    $talent->talentTypes()->attach([
        $model->id => ['is_primary' => true, 'position' => 0],
        $photographer->id => ['is_primary' => false, 'position' => 1],
    ]);
    $talent->load('talentTypes');

    $created = app(SeedProfileBlocks::class)($talent);

    $blockTypeIds = $talent->profileBlocks()->pluck('block_type_id');
    expect($blockTypeIds)->toHaveCount($blockTypeIds->unique()->count()); // no duplicates
    expect($created->count())->toBe($blockTypeIds->count());
    expect($talent->fresh()->status->getValue())->toBe('draft');

    // Blocks from both professions are present.
    $keys = $talent->profileBlocks()->with('blockType')->get()->map(fn ($b) => $b->blockType->key);
    expect($keys->all())->toContain('hero')->toContain('comp_card')->toContain('showreel');
});

it('only seeds missing (non-repeatable) blocks on a second call', function () {
    $talent = Talent::factory()->create(['status' => 'created', 'display_name' => 'Grower']);
    $model = TalentType::where('slug', 'model')->firstOrFail();
    $talent->talentTypes()->attach($model->id, ['is_primary' => true, 'position' => 0]);
    $talent->load('talentTypes');

    $first = app(SeedProfileBlocks::class)($talent);
    $countAfterFirst = $talent->profileBlocks()->count();

    // Calling again seeds nothing new (all non-repeatable blocks already present).
    $second = app(SeedProfileBlocks::class)($talent->fresh('talentTypes'));

    expect($first->count())->toBeGreaterThan(0);
    expect($second->count())->toBe(0);
    expect($talent->profileBlocks()->count())->toBe($countAfterFirst);
});
