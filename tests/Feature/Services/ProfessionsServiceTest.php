<?php

use App\Models\Talent;
use App\Models\TalentType;
use App\Services\ProfessionsService;
use Database\Seeders\BlockTypeSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed([TalentTypeSeeder::class, BlockTypeSeeder::class]));

it('adds a type, seeds its blocks and makes the first one primary', function () {
    $talent = Talent::factory()->create(['status' => 'created', 'display_name' => 'P']);
    $model = TalentType::where('slug', 'model')->firstOrFail();

    app(ProfessionsService::class)->addType($talent, $model);
    $talent->refresh()->load('talentTypes');

    expect($talent->talentTypes)->toHaveCount(1);
    expect((bool) $talent->talentTypes->first()->pivot->is_primary)->toBeTrue();
    expect($talent->profileBlocks()->count())->toBeGreaterThan(0);
    expect($talent->status->getValue())->toBe('draft'); // Created → Draft after seeding
});

it('adding a second type seeds only missing blocks and keeps the first primary', function () {
    $talent = Talent::factory()->create(['status' => 'created', 'display_name' => 'P']);
    $service = app(ProfessionsService::class);
    $model = TalentType::where('slug', 'model')->firstOrFail();
    $photographer = TalentType::where('slug', 'photographer')->firstOrFail();

    $service->addType($talent, $model);
    $afterModel = $talent->profileBlocks()->count();

    $service->addType($talent->fresh(), $photographer);
    $talent->refresh();

    expect($talent->talentTypes()->count())->toBe(2);
    expect($talent->profileBlocks()->count())->toBeGreaterThan($afterModel); // showreel/equipment added

    $ids = $talent->profileBlocks()->pluck('block_type_id');
    expect($ids)->toHaveCount($ids->unique()->count()); // no duplicates

    expect($talent->talentTypes()->wherePivot('is_primary', true)->first()->slug)->toBe('model');
});

it('blocks duplicate professions', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $model = TalentType::where('slug', 'model')->firstOrFail();
    $service = app(ProfessionsService::class);
    $service->addType($talent, $model);

    expect(fn () => $service->addType($talent->fresh(), $model))->toThrow(InvalidArgumentException::class);
});

it('reassigns the primary and reorders professions', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $service = app(ProfessionsService::class);
    $model = TalentType::where('slug', 'model')->firstOrFail();
    $photographer = TalentType::where('slug', 'photographer')->firstOrFail();

    $service->addType($talent, $model);
    $service->addType($talent->fresh(), $photographer);

    $service->setPrimary($talent->fresh(), $photographer);
    expect($talent->talentTypes()->wherePivot('is_primary', true)->first()->slug)->toBe('photographer');
    expect($talent->talentTypes()->wherePivot('is_primary', true)->count())->toBe(1);

    $service->reorderTypes($talent->fresh(), [$photographer->id, $model->id]);
    $position = (int) $talent->talentTypes()->where('talent_types.id', $photographer->id)->first()->pivot->position;
    expect($position)->toBe(0);
});

it('removes a profession', function () {
    $talent = Talent::factory()->create(['display_name' => 'P']);
    $model = TalentType::where('slug', 'model')->firstOrFail();
    $service = app(ProfessionsService::class);
    $service->addType($talent, $model);

    $service->removeType($talent->fresh(), $model);

    expect($talent->talentTypes()->count())->toBe(0);
});
