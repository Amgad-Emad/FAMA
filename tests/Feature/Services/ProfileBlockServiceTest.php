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

it('offers only active, eligible block types the talent can add', function () {
    $talent = makeTalentOfCategory('model');
    $type = $talent->talentTypes->first();

    $universal = BlockType::factory()->create(['availability' => 'universal', 'is_active' => true]);
    $modelBlock = blockGatedToCategory('model');
    $crewBlock = blockGatedToCategory('crew');
    $typeBlock = BlockType::factory()->create(['availability' => 'by_type', 'is_active' => true]);
    $typeBlock->talentTypes()->attach($type->id);
    $foreignTypeBlock = BlockType::factory()->create(['availability' => 'by_type', 'is_active' => true]);
    $foreignTypeBlock->talentTypes()->attach(TalentType::factory()->create()->id);
    $inactive = BlockType::factory()->create(['availability' => 'universal', 'is_active' => false]);

    $available = app(ProfileBlockService::class)->availableBlockTypes($talent)->pluck('id');

    expect($available)->toContain($universal->id)->toContain($modelBlock->id)->toContain($typeBlock->id);
    expect($available)->not->toContain($crewBlock->id);        // wrong category
    expect($available)->not->toContain($foreignTypeBlock->id); // wrong type
    expect($available)->not->toContain($inactive->id);         // inactive
});

it('omits a non-repeatable block that is already on the profile', function () {
    $talent = makeTalentOfCategory('model');
    $block = BlockType::factory()->create(['availability' => 'universal', 'is_active' => true, 'is_repeatable' => false]);
    $talent->profileBlocks()->create([
        'block_type_id' => $block->id, 'title' => ['en' => 'x'], 'position' => 0,
        'is_visible' => true, 'status' => 'visible', 'settings' => [],
    ]);

    $available = app(ProfileBlockService::class)->availableBlockTypes($talent->fresh('talentTypes', 'profileBlocks'))->pluck('id');

    expect($available)->not->toContain($block->id);
});

it('adds an eligible block and rejects an ineligible one', function () {
    $talent = makeTalentOfCategory('model');
    $service = app(ProfileBlockService::class);

    $universal = BlockType::factory()->create(['availability' => 'universal', 'is_active' => true]);
    expect($service->addBlock($talent, $universal))->toBeInstanceOf(ProfileBlock::class);

    $crewOnly = blockGatedToCategory('crew');
    expect(fn () => $service->addBlock($talent->fresh('talentTypes', 'profileBlocks'), $crewOnly))
        ->toThrow(InvalidArgumentException::class);
});

it('fills, reorders, hides (syncing is_visible) and removes blocks', function () {
    $talent = makeTalentOfCategory('model');
    $service = app(ProfileBlockService::class);
    $type = BlockType::factory()->create(['availability' => 'universal', 'is_active' => true, 'is_repeatable' => true]);

    $a = $service->addBlock($talent, $type);
    $b = $service->addBlock($talent->fresh('talentTypes', 'profileBlocks'), $type);

    $service->fillBlock($a, ['title' => ['en' => 'Filled'], 'content' => ['x' => 1]]);
    expect($a->fresh()->getTranslation('title', 'en'))->toBe('Filled');
    expect($a->fresh()->content)->toBe(['x' => 1]);

    $service->reorder($talent->fresh(), [$b->id, $a->id]);
    expect($b->fresh()->position)->toBe(0);
    expect($a->fresh()->position)->toBe(1);

    $service->setVisibility($a, false);
    expect($a->fresh()->status->getValue())->toBe('hidden');
    expect($a->fresh()->is_visible)->toBeFalse();

    $service->removeBlock($b);
    expect(ProfileBlock::find($b->id))->toBeNull();
});

it('rejects reordering a block that belongs to another talent', function () {
    $talent = makeTalentOfCategory('model');
    $foreign = ProfileBlock::factory()->create();

    expect(fn () => app(ProfileBlockService::class)->reorder($talent, [$foreign->id]))
        ->toThrow(InvalidArgumentException::class);
});
