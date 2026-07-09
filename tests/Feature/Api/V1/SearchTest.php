<?php

use App\Models\Brand;

// The public brand directory (spatie/laravel-query-builder). Talent search is
// covered by ContractTest; this focuses on the new brand-discovery contract.

it('returns a paginated, published-only brand directory', function () {
    Brand::factory()->count(15)->create(['is_published' => true, 'status' => 'published']);
    Brand::factory()->unpublished()->create();

    $res = $this->getJson('/api/v1/brands')->assertOk()
        ->assertJsonStructure(['success', 'data', 'meta' => ['pagination' => ['total', 'per_page', 'current_page']]]);

    expect($res->json('meta.pagination.total'))->toBe(15)
        ->and($res->json('meta.pagination.per_page'))->toBe(12)
        ->and(count($res->json('data')))->toBe(12);
});

it('filters by the whitelisted fields', function () {
    Brand::factory()->create(['is_published' => true, 'status' => 'published', 'industry' => 'food_beverage', 'base_city' => 'Cairo', 'is_verified' => true, 'name' => 'Nomad Coffee']);
    Brand::factory()->create(['is_published' => true, 'status' => 'published', 'industry' => 'fashion', 'base_city' => 'Dubai', 'is_verified' => false, 'name' => 'Atelier']);

    $this->getJson('/api/v1/brands?filter[industry]=food_beverage')->assertOk()->assertJsonPath('meta.pagination.total', 1);
    $this->getJson('/api/v1/brands?filter[city]=Cairo')->assertOk()->assertJsonPath('meta.pagination.total', 1);
    $this->getJson('/api/v1/brands?filter[verified]=1')->assertOk()->assertJsonPath('meta.pagination.total', 1);
    $this->getJson('/api/v1/brands?filter[q]=nomad')->assertOk()->assertJsonPath('meta.pagination.total', 1);
});

it('sorts by a whitelisted sort', function () {
    Brand::factory()->create(['is_published' => true, 'status' => 'published', 'name' => 'Zeta']);
    Brand::factory()->create(['is_published' => true, 'status' => 'published', 'name' => 'Alpha']);

    $names = collect($this->getJson('/api/v1/brands?sort=name')->assertOk()->json('data'))->pluck('name');
    expect($names->first())->toBe('Alpha');
});

it('rejects an unknown filter with a 400 envelope', function () {
    $this->getJson('/api/v1/brands?filter[secret]=x')
        ->assertStatus(400)
        ->assertJsonPath('success', false);
});

it('returns brand descriptions in the Accept-Language locale', function () {
    $brand = Brand::factory()->create(['is_published' => true, 'status' => 'published']);
    $brand->setTranslation('description', 'en', 'Coffee')->setTranslation('description', 'ar', 'قهوة')->save();

    $data = $this->withHeaders(['Accept-Language' => 'ar'])->getJson('/api/v1/brands')->assertOk()->json('data');
    expect(collect($data)->firstWhere('id', $brand->id)['description'])->toBe('قهوة');
});
