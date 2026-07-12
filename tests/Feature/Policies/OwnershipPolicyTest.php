<?php

use App\Models\ProfileBlock;
use App\Models\Talent;
use App\Policies\ProfileBlockPolicy;
use App\Policies\TalentPolicy;

it('lets a talent manage its own resources but not another talent’s', function () {
    $owner = Talent::factory()->create();
    $other = Talent::factory()->create();

    $block = ProfileBlock::factory()->for($owner)->create();
    expect((new ProfileBlockPolicy)->update($owner, $block))->toBeTrue();
    expect((new ProfileBlockPolicy)->update($other, $block))->toBeFalse();
});

it('guards the talent profile itself', function () {
    $owner = Talent::factory()->create();
    $other = Talent::factory()->create();

    expect((new TalentPolicy)->update($owner, $owner))->toBeTrue();
    expect((new TalentPolicy)->update($other, $owner))->toBeFalse();
});
