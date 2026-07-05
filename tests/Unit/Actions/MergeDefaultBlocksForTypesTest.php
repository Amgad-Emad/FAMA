<?php

use App\Actions\MergeDefaultBlocksForTypes;
use App\Models\TalentType;
use Illuminate\Support\Collection;
use Tests\TestCase;

// Boot the framework (no DB) so model factories resolve; the action itself is pure.
uses(TestCase::class);

it('merges and de-dupes default blocks in first-seen order', function () {
    $model = TalentType::factory()->make(['default_blocks' => ['hero', 'gallery', 'comp_card']]);
    $photographer = TalentType::factory()->make(['default_blocks' => ['hero', 'gallery', 'showreel', 'equipment']]);

    $merged = (new MergeDefaultBlocksForTypes)(new Collection([$model, $photographer]));

    expect($merged)->toBe(['hero', 'gallery', 'comp_card', 'showreel', 'equipment']);
});

it('returns an empty list when there are no types', function () {
    expect((new MergeDefaultBlocksForTypes)(new Collection))->toBe([]);
});

it('tolerates a type with no default blocks', function () {
    $a = TalentType::factory()->make(['default_blocks' => ['hero']]);
    $b = TalentType::factory()->make(['default_blocks' => []]);

    expect((new MergeDefaultBlocksForTypes)(new Collection([$a, $b])))->toBe(['hero']);
});
