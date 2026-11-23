<?php

use App\Models\ProfileBlock;
use App\Models\Talent;
use App\Models\TalentType;
use App\Services\SkillsService;
use App\Services\TalentProjectService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\SpecialitySeeder;
use Database\Seeders\TalentTypeSeeder;

/*
 * Projects is a fixed part of every skill tab (2026-11-23): seeded for every
 * skill, never hidden / moved / removed, and always rendered on the public
 * profile while the tab has work — even when the block itself is missing.
 */

beforeEach(function () {
    $this->withoutVite();
    $this->seed([TalentTypeSeeder::class, SpecialitySeeder::class, BlockTypeSeeder::class]);
});

/** A published talent holding the given skills, each seeded the normal way. */
function pinnedTalent(string ...$skills): Talent
{
    $talent = Talent::factory()->create(['display_name' => 'Pinned', 'is_published' => true, 'is_verified' => true]);
    foreach ($skills as $slug) {
        app(SkillsService::class)->addType($talent, TalentType::where('slug', $slug)->firstOrFail());
    }

    return $talent->fresh();
}

/** The talent's Projects block in a skill's tab. */
function projectsBlockIn(Talent $talent, string $slug): ?ProfileBlock
{
    return $talent->profileBlocks()
        ->where('talent_type_id', TalentType::where('slug', $slug)->value('id'))
        ->whereRelation('blockType', 'key', 'projects')
        ->first();
}

it('seeds a Projects section into every skill tab a talent adds', function () {
    $talent = pinnedTalent('photography', 'modeling', 'styling', 'makeup');

    foreach (['photography', 'modeling', 'styling', 'makeup'] as $slug) {
        expect(projectsBlockIn($talent, $slug))->not->toBeNull();
    }
});

it('refuses to hide, move or remove a skill tab\'s Projects section', function () {
    $talent = pinnedTalent('photography', 'modeling');
    $block = projectsBlockIn($talent, 'photography');

    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.blocks.visibility', $block->id), ['is_visible' => false])
        ->assertStatus(422);
    $this->actingAs($talent, 'talent')
        ->patchJson(route('talent.profile.blocks.move', $block->id), ['talent_type_id' => null])
        ->assertStatus(422);

    expect($block->fresh())->is_visible->toBeTrue()->talent_type_id->toBe($block->talent_type_id);
});

it('shows a tab\'s projects even when its Projects block is missing', function () {
    $talent = pinnedTalent('photography');
    app(TalentProjectService::class)->create($talent, ['title' => ['en' => 'Desert light']], [$talent->talentTypes()->firstOrFail()->id]);

    // A legacy / hand-built profile: the Projects block is gone.
    projectsBlockIn($talent, 'photography')->delete();

    $this->get('/'.$talent->slug)->assertOk()->assertSee('Desert light');
});
