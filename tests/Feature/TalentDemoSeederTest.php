<?php

use App\Models\Talent;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentDemoSeeder;
use Database\Seeders\TalentTypeSeeder;

it('seeds a multi-type demo talent with merged blocks and populated content', function () {
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class, TalentDemoSeeder::class]);

    $talent = Talent::with(['talentTypes', 'profileBlocks.blockType'])
        ->where('slug', 'demo-talent')
        ->firstOrFail();

    // Two professions, model leads.
    expect($talent->talentTypes->pluck('slug')->all())
        ->toContain('model')
        ->toContain('photographer');

    $primary = $talent->talentTypes->first(fn ($type) => (bool) $type->pivot->is_primary);
    expect($primary->slug)->toBe('model');

    // Blocks are the merged + deduped default_blocks of both professions.
    $keys = $talent->profileBlocks->map(fn ($block) => $block->blockType->key);
    expect($keys->all())
        ->toContain('hero')
        ->toContain('gallery')
        ->toContain('comp_card')   // from model
        ->toContain('showreel')    // from photographer
        ->toContain('equipment');  // from photographer
    expect($keys->count())->toBe($keys->unique()->count()); // no duplicates

    // Content tables are populated for later phases to render.
    expect($talent->portfolioItems()->count())->toBeGreaterThan(0);
    expect($talent->compCard()->exists())->toBeTrue();
    expect($talent->reviews()->approved()->count())->toBeGreaterThan(0);
    expect($talent->services()->count())->toBeGreaterThan(0);
});

it('is idempotent — re-seeding leaves a single demo talent', function () {
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
    $this->seed(TalentDemoSeeder::class);
    $this->seed(TalentDemoSeeder::class);

    expect(Talent::where('slug', 'demo-talent')->count())->toBe(1);
});
