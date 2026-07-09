<?php

use App\Models\Brand;
use App\Models\Talent;

beforeEach(function () {
    // Open reach so the feed's same-city narrowing doesn't drop seeded talents
    // (the factory randomizes geographic_reach); no creativeNeed = all published.
    $this->brand = Brand::factory()->create(['geographic_reach' => 'mena']);
    $this->token = $this->brand->createToken('t', ['brand'])->plainTextToken;
});

it('requires a brand token', function () {
    $this->getJson('/api/v1/brand/discover')->assertUnauthorized();
});

it('returns a paginated discovery feed of published talents', function () {
    Talent::factory()->count(15)->create(['is_published' => true, 'status' => 'live']);
    Talent::factory()->create(['is_published' => false, 'status' => 'draft']); // excluded

    $res = api()->withToken($this->token)->getJson('/api/v1/brand/discover')->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['pagination' => ['total', 'per_page']]]);

    expect($res->json('meta.pagination.total'))->toBe(15)
        ->and($res->json('meta.pagination.per_page'))->toBe(12);
});

it('narrows the feed by an ad-hoc filter', function () {
    Talent::factory()->count(3)->create(['is_published' => true, 'status' => 'live', 'base_city' => 'Cairo']);
    Talent::factory()->count(2)->create(['is_published' => true, 'status' => 'live', 'base_city' => 'Dubai']);

    api()->withToken($this->token)->getJson('/api/v1/brand/discover?filter[city]=Cairo')
        ->assertOk()->assertJsonPath('meta.pagination.total', 3);
});

it('writes a save signal and a brief signal', function () {
    $talent = Talent::factory()->create(['is_published' => true, 'status' => 'live']);

    api()->withToken($this->token)->postJson('/api/v1/brand/discover/save', ['talent_id' => $talent->id])->assertOk();
    api()->withToken($this->token)->postJson('/api/v1/brand/discover/brief', ['talent_id' => $talent->id])->assertOk();

    $this->assertDatabaseHas('brand_signals', ['brand_id' => $this->brand->id, 'talent_id' => $talent->id, 'action_type' => 'save']);
    $this->assertDatabaseHas('brand_signals', ['brand_id' => $this->brand->id, 'talent_id' => $talent->id, 'action_type' => 'brief_sent']);
});

it('validates the signal target', function () {
    api()->withToken($this->token)->postJson('/api/v1/brand/discover/save', ['talent_id' => 999999])
        ->assertStatus(422)->assertJsonValidationErrors('talent_id');
});
