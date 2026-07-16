<?php

use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\ProfileBlockService;

function makeTalentOfCategory(string $category): Talent
{
    $talent = Talent::factory()->create();
    $type = TalentType::factory()->create(['category' => $category]);
    $talent->talentTypes()->attach($type->id, ['is_primary' => true, 'position' => 0]);

    return $talent->load('talentTypes', 'profileBlocks');
}

function blockGatedToCategory(string $category): BlockType
{
    $block = BlockType::factory()->create(['availability' => 'by_category', 'is_active' => true]);
    $block->categories()->create(['category' => $category]);

    return $block;
}

it('offers, IN A SKILL TAB, only active, eligible block types', function () {
    $talent = makeTalentOfCategory('model');
    $skill = $talent->talentTypes->first();

    $universal = BlockType::factory()->create(['availability' => 'universal', 'is_active' => true]);
    $modelBlock = blockGatedToCategory('model');
    $crewBlock = blockGatedToCategory('crew');
    $typeBlock = BlockType::factory()->create(['availability' => 'by_type', 'is_active' => true]);
    $typeBlock->talentTypes()->attach($skill->id);
    $foreignTypeBlock = BlockType::factory()->create(['availability' => 'by_type', 'is_active' => true]);
    $foreignTypeBlock->talentTypes()->attach(TalentType::factory()->create()->id);
    $inactive = BlockType::factory()->create(['availability' => 'universal', 'is_active' => false]);

    $available = app(ProfileBlockService::class)->availableBlockTypes($talent, $skill)->pluck('id');

    expect($available)->toContain($universal->id)->toContain($modelBlock->id)->toContain($typeBlock->id);
    expect($available)->not->toContain($crewBlock->id);        // wrong category
    expect($available)->not->toContain($foreignTypeBlock->id); // wrong type
    expect($available)->not->toContain($inactive->id);         // inactive
});

it('offers ONLY universal blocks in the universal (profile-level) section', function () {
    $talent = makeTalentOfCategory('model');

    $universal = BlockType::factory()->create(['availability' => 'universal', 'is_active' => true]);
    $modelBlock = blockGatedToCategory('model');

    $available = app(ProfileBlockService::class)->availableBlockTypes($talent, null)->pluck('id');

    expect($available)->toContain($universal->id);
    expect($available)->not->toContain($modelBlock->id); // gated blocks can't go profile-level
});

it('omits a non-repeatable block already present IN THAT SCOPE (per-scope repeatability)', function () {
    $talent = makeTalentOfCategory('model');
    $skill = $talent->talentTypes->first();
    $block = BlockType::factory()->create(['availability' => 'universal', 'is_active' => true, 'is_repeatable' => false]);

    // Present in the skill tab.
    $talent->profileBlocks()->create([
        'block_type_id' => $block->id, 'talent_type_id' => $skill->id, 'title' => ['en' => 'x'],
        'position' => 0, 'is_visible' => true, 'status' => 'visible', 'settings' => [],
    ]);
    $talent->load('profileBlocks');

    // Omitted in the skill tab, but still offered in the (empty) universal section.
    expect(app(ProfileBlockService::class)->availableBlockTypes($talent, $skill)->pluck('id'))->not->toContain($block->id);
    expect(app(ProfileBlockService::class)->availableBlockTypes($talent, null)->pluck('id'))->toContain($block->id);
});

it('adds an eligible block into a tab and rejects an ineligible one', function () {
    $talent = makeTalentOfCategory('model');
    $skill = $talent->talentTypes->first();
    $service = app(ProfileBlockService::class);

    $universal = BlockType::factory()->create(['availability' => 'universal', 'is_active' => true]);
    $block = $service->addBlock($talent, $universal, $skill);
    expect($block)->toBeInstanceOf(ProfileBlock::class);
    expect($block->talent_type_id)->toBe($skill->id);

    $crewOnly = blockGatedToCategory('crew');
    expect(fn () => $service->addBlock($talent->fresh('talentTypes', 'profileBlocks'), $crewOnly, $skill))
        ->toThrow(InvalidArgumentException::class);
});

it('reorders blocks within a scope only (tabs are independent)', function () {
    $talent = makeTalentOfCategory('model');
    $skill = $talent->talentTypes->first();
    $service = app(ProfileBlockService::class);
    $type = BlockType::factory()->create(['availability' => 'universal', 'is_active' => true, 'is_repeatable' => true]);

    $a = $service->addBlock($talent, $type, $skill);
    $b = $service->addBlock($talent->fresh('talentTypes', 'profileBlocks'), $type, $skill);
    $u = $service->addBlock($talent->fresh('talentTypes', 'profileBlocks'), $type, null); // universal

    $service->reorder($talent->fresh(), $skill, [$b->id, $a->id]);
    expect($b->fresh()->position)->toBe(0);
    expect($a->fresh()->position)->toBe(1);

    // A block from another scope cannot be reordered here.
    expect(fn () => $service->reorder($talent->fresh(), $skill, [$u->id]))
        ->toThrow(InvalidArgumentException::class);
});

it('moves a block between scopes when eligible and rejects an ineligible move', function () {
    $talent = Talent::factory()->create();
    $model = TalentType::factory()->create(['category' => 'model']);
    $crew = TalentType::factory()->create(['category' => 'crew']);
    $talent->talentTypes()->attach([$model->id => ['is_primary' => true, 'position' => 0], $crew->id => ['is_primary' => false, 'position' => 1]]);
    $talent->load('talentTypes', 'profileBlocks');
    $service = app(ProfileBlockService::class);

    // A universal block moves freely between tabs.
    $universal = BlockType::factory()->create(['availability' => 'universal', 'is_active' => true, 'is_repeatable' => true]);
    $block = $service->addBlock($talent, $universal, $model);
    $moved = $service->moveBlock($block, $crew);
    expect($moved->talent_type_id)->toBe($crew->id);

    // A crew-gated block cannot move into the model tab.
    $crewBlock = blockGatedToCategory('crew');
    $crewOwned = $service->addBlock($talent->fresh('talentTypes', 'profileBlocks'), $crewBlock, $crew);
    expect(fn () => $service->moveBlock($crewOwned, $model))
        ->toThrow(InvalidArgumentException::class);
});

it('fills, hides (syncing is_visible) and removes blocks', function () {
    $talent = makeTalentOfCategory('model');
    $skill = $talent->talentTypes->first();
    $service = app(ProfileBlockService::class);
    $type = BlockType::factory()->create(['availability' => 'universal', 'is_active' => true, 'is_repeatable' => true]);

    $a = $service->addBlock($talent, $type, $skill);
    $service->fillBlock($a, ['title' => ['en' => 'Filled'], 'content' => ['x' => 1]]);
    expect($a->fresh()->getTranslation('title', 'en'))->toBe('Filled');
    expect($a->fresh()->content)->toBe(['x' => 1]);

    $service->setVisibility($a, false);
    expect($a->fresh()->status->getValue())->toBe('hidden');
    expect($a->fresh()->is_visible)->toBeFalse();

    $service->removeBlock($a);
    expect(ProfileBlock::find($a->id))->toBeNull();
});

it('rejects reordering a block that belongs to another talent', function () {
    $talent = makeTalentOfCategory('model');
    $foreign = ProfileBlock::factory()->create();

    expect(fn () => app(ProfileBlockService::class)->reorder($talent, null, [$foreign->id]))
        ->toThrow(InvalidArgumentException::class);
});
