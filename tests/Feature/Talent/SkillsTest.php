<?php

use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]));

it('adds a skill (seeding blocks), sets primary, reorders and removes', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $photographer = TalentType::where('slug', 'photography')->firstOrFail();

    $this->actingAs($talent, 'talent')->postJson(route('talent.profile.skills.store'), ['talent_type_id' => $model->id])->assertCreated();
    expect($talent->fresh()->profileBlocks()->count())->toBeGreaterThan(0);

    $this->actingAs($talent, 'talent')->postJson(route('talent.profile.skills.store'), ['talent_type_id' => $photographer->id])->assertCreated();
    $this->actingAs($talent, 'talent')->patchJson(route('talent.profile.skills.primary', $photographer->id))->assertOk();
    expect($talent->fresh()->talentTypes()->wherePivot('is_primary', true)->first()->slug)->toBe('photography');

    $this->actingAs($talent, 'talent')->patchJson(route('talent.profile.skills.reorder'), ['order' => [$photographer->id, $model->id]])->assertOk();
    $this->actingAs($talent, 'talent')->deleteJson(route('talent.profile.skills.destroy', $model->id))->assertOk();
    expect($talent->fresh()->talentTypes()->count())->toBe(1);
});

it('rejects a duplicate skill with 422', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();

    $this->actingAs($talent, 'talent')->postJson(route('talent.profile.skills.store'), ['talent_type_id' => $model->id])->assertCreated();
    $this->actingAs($talent, 'talent')->postJson(route('talent.profile.skills.store'), ['talent_type_id' => $model->id])->assertStatus(422);
});

it('returns the skills data payload for the editor', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $model = TalentType::where('slug', 'modeling')->firstOrFail();
    $this->actingAs($talent, 'talent')->postJson(route('talent.profile.skills.store'), ['talent_type_id' => $model->id])->assertCreated();

    $this->actingAs($talent, 'talent')->getJson(route('talent.profile.skills.data'))
        ->assertOk()
        ->assertJsonPath('data.linked.0.slug', 'modeling');
});
