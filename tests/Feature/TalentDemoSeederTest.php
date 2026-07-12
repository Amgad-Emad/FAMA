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

    // Two skills, model leads.
    expect($talent->talentTypes->pluck('slug')->all())
        ->toContain('modeling')
        ->toContain('photography');

    $primary = $talent->talentTypes->first(fn ($type) => (bool) $type->pivot->is_primary);
    expect($primary->slug)->toBe('modeling');

    // Blocks are scope-aware (ADR-Q): universal talent-level blocks at profile level,
    // gated + gallery blocks in each skill's own tab.
    $model = $talent->talentTypes->firstWhere('slug', 'modeling');
    $photographer = $talent->talentTypes->firstWhere('slug', 'photography');

    $universalKeys = $talent->profileBlocks->whereNull('talent_type_id')->map(fn ($b) => $b->blockType->key);
    expect($universalKeys->all())->toContain('hero')->toContain('reviews')->toContain('brand_collabs');

    $modelKeys = $talent->profileBlocks->where('talent_type_id', $model->id)->map(fn ($b) => $b->blockType->key);
    expect($modelKeys->all())->toContain('gallery')->toContain('comp_card');

    $photographerKeys = $talent->profileBlocks->where('talent_type_id', $photographer->id)->map(fn ($b) => $b->blockType->key);
    expect($photographerKeys->all())->toContain('gallery')->toContain('showreel')->toContain('equipment');

    // A gallery block exists in BOTH tabs (different bodies of work).
    expect($talent->profileBlocks->filter(fn ($b) => $b->blockType->key === 'gallery'))->toHaveCount(2);

    // No duplicate block_type WITHIN a scope.
    foreach ([$universalKeys, $modelKeys, $photographerKeys] as $scopeKeys) {
        expect($scopeKeys->count())->toBe($scopeKeys->unique()->count());
    }

    // Content tables are populated for later phases to render.
    expect($talent->portfolioItems()->count())->toBeGreaterThan(0);
    expect($talent->compCard()->exists())->toBeTrue();
    expect($talent->reviews()->approved()->count())->toBeGreaterThan(0);
});

it('is idempotent — re-seeding leaves a single demo talent', function () {
    $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]);
    $this->seed(TalentDemoSeeder::class);
    $this->seed(TalentDemoSeeder::class);

    expect(Talent::where('slug', 'demo-talent')->count())->toBe(1);
});
