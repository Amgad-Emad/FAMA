<?php

use App\Actions\SeedBlocksForSkill;
use App\Models\BlockType;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]));

/** Attach a skill (pivot only) so we can seed its tab in isolation. */
function attachSkill(Talent $talent, TalentType $type, bool $primary = false, int $position = 0): void
{
    $talent->talentTypes()->attach($type->id, ['is_primary' => $primary, 'position' => $position]);
    $talent->load('talentTypes');
}

it('seeds a skill’s default blocks into that skill’s scope and moves Created → Draft', function () {
    $talent = Talent::factory()->create(['status' => 'created', 'display_name' => 'M']);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    attachSkill($talent, $model, true);

    $created = app(SeedBlocksForSkill::class)($talent, $model);

    // Every block seeded INTO THE SKILL is stamped with it. The call also seeds
    // the profile-level defaults (ADR-Z) — Recognitions and Willing to explore
    // describe the person, not a discipline, so they carry a NULL scope and must
    // not be repeated per tab.
    $profileDefaults = BlockType::where('is_profile_default', true)->pluck('id');

    [$universal, $scoped] = $created->partition(fn ($b) => $b->talent_type_id === null);

    expect($scoped->every(fn ($b) => $b->talent_type_id === $model->id))->toBeTrue()
        ->and($universal->pluck('block_type_id')->sort()->values()->all())
        ->toBe($profileDefaults->sort()->values()->all());
    // model default_blocks: gallery, comp_card, look_types, digitals, brand_collabs, reviews
    $keys = $talent->profileBlocks()->where('talent_type_id', $model->id)->with('blockType')->get()->map(fn ($b) => $b->blockType->key);
    expect($keys->all())->toContain('gallery')->toContain('comp_card');
    expect($talent->fresh()->status->getValue())->toBe('draft');
});

it('adding a second skill creates that skill’s OWN blocks — including a second gallery — without cross-skill dedupe', function () {
    $talent = Talent::factory()->create(['status' => 'created', 'display_name' => 'MP']);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();

    attachSkill($talent, $model, true, 0);
    app(SeedBlocksForSkill::class)($talent, $model);

    attachSkill($talent, $photographer, false, 1);
    app(SeedBlocksForSkill::class)($talent->fresh('talentTypes'), $photographer);

    // A gallery block exists in BOTH tabs (different bodies of work).
    $galleries = $talent->profileBlocks()->whereRelation('blockType', 'key', 'gallery')->get();
    expect($galleries)->toHaveCount(2);
    // Sort BOTH sides: the assertion is "one gallery per skill", not "modeling
    // has the lower id" — which is all that used to make the unsorted
    // expectation line up.
    expect($galleries->pluck('talent_type_id')->sort()->values()->all())
        ->toBe(collect([$model->id, $photographer->id])->sort()->values()->all());

    // Within a single skill, no duplicate block_type.
    $modelTypeIds = $talent->profileBlocks()->where('talent_type_id', $model->id)->pluck('block_type_id');
    expect($modelTypeIds)->toHaveCount($modelTypeIds->unique()->count());
});

it('is idempotent within a skill scope (a second call seeds nothing new)', function () {
    $talent = Talent::factory()->create(['status' => 'created', 'display_name' => 'Idem']);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    attachSkill($talent, $model, true);

    $first = app(SeedBlocksForSkill::class)($talent, $model);
    $second = app(SeedBlocksForSkill::class)($talent->fresh('talentTypes'), $model);

    expect($first->count())->toBeGreaterThan(0);
    expect($second->count())->toBe(0);
});
