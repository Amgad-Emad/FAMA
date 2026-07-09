<?php

use App\Models\Brand;
use App\Models\TalentType;

beforeEach(function () {
    $this->brand = Brand::factory()->incomplete()->create();
    $this->token = $this->brand->createToken('t', ['brand'])->plainTextToken;
});

it('requires a brand token', function () {
    $this->getJson('/api/v1/brand/onboarding')->assertUnauthorized();
});

it('reports onboarding status', function () {
    api()->withToken($this->token)->getJson('/api/v1/brand/onboarding')
        ->assertOk()->assertJsonPath('data.is_complete', false)
        ->assertJsonStructure(['data' => ['name', 'industry', 'talent_type_ids', 'mood_tags']]);
});

it('walks the six steps and completes onboarding', function () {
    $type = TalentType::factory()->create();

    api()->withToken($this->token)->postJson('/api/v1/brand/onboarding/identity', [
        'name' => 'Nomad Coffee', 'industry' => 'food_beverage', 'brand_stage' => 'growing',
    ])->assertOk();

    api()->withToken($this->token)->postJson('/api/v1/brand/onboarding/location', [
        'base_city' => 'Cairo', 'base_country' => 'Egypt', 'geographic_reach' => 'mena',
    ])->assertOk();

    api()->withToken($this->token)->postJson('/api/v1/brand/onboarding/creative-needs', [
        'talent_type_ids' => [$type->id], 'project_types' => ['editorial'], 'project_frequency' => 'monthly',
    ])->assertOk();

    api()->withToken($this->token)->postJson('/api/v1/brand/onboarding/aesthetic', [
        'mood_tags' => ['minimal', 'warm'], 'brand_references' => 'Kinfolk',
    ])->assertOk();

    api()->withToken($this->token)->postJson('/api/v1/brand/onboarding/budget', ['budget_tier' => '2000_10000'])->assertOk();

    api()->withToken($this->token)->postJson('/api/v1/brand/onboarding/complete')
        ->assertCreated()->assertJsonPath('data.is_complete', true);

    expect((bool) $this->brand->fresh()->is_complete)->toBeTrue();
});

it('validates each step', function () {
    api()->withToken($this->token)->postJson('/api/v1/brand/onboarding/identity', ['name' => 'X'])
        ->assertStatus(422)->assertJsonValidationErrors(['industry', 'brand_stage']);

    api()->withToken($this->token)->postJson('/api/v1/brand/onboarding/location', ['base_city' => 'Cairo'])
        ->assertStatus(422)->assertJsonValidationErrors(['base_country', 'geographic_reach']);

    api()->withToken($this->token)->postJson('/api/v1/brand/onboarding/budget', ['budget_tier' => 'infinite'])
        ->assertStatus(422)->assertJsonValidationErrors('budget_tier');
});
