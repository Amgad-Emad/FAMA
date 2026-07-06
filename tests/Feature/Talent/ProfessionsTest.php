<?php

use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]));

it('adds a profession (seeding blocks), sets primary, reorders and removes', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $model = TalentType::where('slug', 'model')->firstOrFail();
    $photographer = TalentType::where('slug', 'photographer')->firstOrFail();

    $this->actingAs($talent, 'talent')->postJson(route('talent.professions.store'), ['talent_type_id' => $model->id])->assertCreated();
    expect($talent->fresh()->profileBlocks()->count())->toBeGreaterThan(0);

    $this->actingAs($talent, 'talent')->postJson(route('talent.professions.store'), ['talent_type_id' => $photographer->id])->assertCreated();
    $this->actingAs($talent, 'talent')->patchJson(route('talent.professions.primary', $photographer->id))->assertOk();
    expect($talent->fresh()->talentTypes()->wherePivot('is_primary', true)->first()->slug)->toBe('photographer');

    $this->actingAs($talent, 'talent')->patchJson(route('talent.professions.reorder'), ['order' => [$photographer->id, $model->id]])->assertOk();
    $this->actingAs($talent, 'talent')->deleteJson(route('talent.professions.destroy', $model->id))->assertOk();
    expect($talent->fresh()->talentTypes()->count())->toBe(1);
});

it('rejects a duplicate profession with 422', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $model = TalentType::where('slug', 'model')->firstOrFail();

    $this->actingAs($talent, 'talent')->postJson(route('talent.professions.store'), ['talent_type_id' => $model->id])->assertCreated();
    $this->actingAs($talent, 'talent')->postJson(route('talent.professions.store'), ['talent_type_id' => $model->id])->assertStatus(422);
});
