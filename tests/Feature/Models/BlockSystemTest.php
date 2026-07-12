<?php

use App\Models\BlockType;
use App\Models\ProfileBlock;
use App\Models\Talent;
use App\Models\TalentType;

it('casts talent_type default_blocks to an array', function () {
    $type = TalentType::factory()->create(['default_blocks' => ['hero', 'gallery']]);

    expect(TalentType::find($type->id)->default_blocks)->toBe(['hero', 'gallery']);
});

it('translates talent_type name', function () {
    $type = TalentType::factory()->create(['name' => ['en' => 'Modeling', 'ar' => 'عرض الأزياء']]);

    expect(TalentType::find($type->id)->getTranslation('name', 'ar'))->toBe('عرض الأزياء');
});

it('profile block belongs to a talent and a block type', function () {
    $block = ProfileBlock::factory()->create();

    $fresh = ProfileBlock::with(['talent', 'blockType'])->find($block->id);

    expect($fresh->talent)->toBeInstanceOf(Talent::class);
    expect($fresh->blockType)->toBeInstanceOf(BlockType::class);
});

it('always eager loads the block type behind a profile block', function () {
    $block = ProfileBlock::factory()->create();

    // No explicit eager load; the model's $with should have resolved it.
    expect(ProfileBlock::find($block->id)->relationLoaded('blockType'))->toBeTrue();
});

it('casts profile block settings/content and visibility', function () {
    $block = ProfileBlock::factory()->create([
        'settings' => ['columns' => 3],
        'content' => ['text' => 'hi'],
        'is_visible' => false,
    ]);

    $fresh = ProfileBlock::find($block->id);

    expect($fresh->settings)->toBe(['columns' => 3]);
    expect($fresh->content)->toBe(['text' => 'hi']);
    expect($fresh->is_visible)->toBeFalse();
});

it('gates a by_category block to categories', function () {
    $blockType = BlockType::factory()->byCategory()->create();
    $blockType->categories()->create(['category' => 'model']);

    $fresh = BlockType::with('categories')->find($blockType->id);

    expect($fresh->availability)->toBe('by_category');
    expect($fresh->categories->pluck('category')->all())->toBe(['model']);
});

it('gates a by_type block to skills via the pivot', function () {
    $blockType = BlockType::factory()->byType()->create();
    $type = TalentType::factory()->create();
    $blockType->talentTypes()->attach($type->id);

    $fresh = BlockType::with('talentTypes')->find($blockType->id);

    expect($fresh->talentTypes)->toHaveCount(1);
    expect($fresh->talentTypes->first()->id)->toBe($type->id);
});

it('casts block type booleans and settings_schema', function () {
    $blockType = BlockType::factory()->create([
        'is_active' => true,
        'is_repeatable' => false,
        'settings_schema' => ['fields' => ['title']],
    ]);

    $fresh = BlockType::find($blockType->id);

    expect($fresh->is_active)->toBeTrue();
    expect($fresh->is_repeatable)->toBeFalse();
    expect($fresh->settings_schema)->toBe(['fields' => ['title']]);
});
