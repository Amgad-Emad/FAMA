<?php

use App\Models\BlockType;
use App\Models\DealFlow;
use App\Models\TalentType;

// Public reference/lookup catalog — no auth (onboarding needs these pre-login).

it('lists talent types in the request locale', function () {
    TalentType::factory()->create(['name' => ['en' => 'Photographer', 'ar' => 'مصوّر'], 'slug' => 'photographer']);

    $en = $this->getJson('/api/v1/lookups/talent-types')->assertOk();
    expect(collect($en->json('data'))->pluck('name'))->toContain('Photographer');

    $ar = $this->withHeaders(['Accept-Language' => 'ar'])->getJson('/api/v1/lookups/talent-types')->assertOk();
    expect(collect($ar->json('data'))->pluck('name'))->toContain('مصوّر');
});

it('lists only active block types', function () {
    BlockType::factory()->create(['is_active' => true]);
    BlockType::factory()->create(['is_active' => false]);

    $data = $this->getJson('/api/v1/lookups/block-types')->assertOk()->json('data');
    expect($data)->toHaveCount(1);
});

it('lists active deal flows with their steps', function () {
    $flow = DealFlow::factory()->standard()->create();

    $this->getJson('/api/v1/lookups/deal-flows')->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.slug', $flow->slug)
        ->assertJsonStructure(['data' => [['id', 'name', 'steps']]]);
});

it('returns the option lists for dynamic UI', function () {
    $this->getJson('/api/v1/lookups/options')->assertOk()
        ->assertJsonStructure([
            'data' => [
                'brand' => ['industries', 'stages', 'moods', 'budgets', 'social_platforms', 'company_sizes'],
                'talent' => ['availability', 'rate_tiers', 'booking_types', 'representation_types', 'service_price_units'],
            ],
        ])
        ->assertJsonPath('data.brand.industries.0', 'fashion');
});
