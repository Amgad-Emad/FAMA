<?php

use App\Models\Brand;
use App\Models\BrandCredibility;
use App\Models\BrandReview;
use App\Models\TalentType;

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->token = $this->brand->createToken('t', ['brand'])->plainTextToken;
});

// --- Creative needs --------------------------------------------------------

it('reads and updates creative needs', function () {
    $type = TalentType::factory()->create();

    api()->withToken($this->token)->getJson('/api/v1/brand/creative-needs')->assertOk()
        ->assertJsonStructure(['data' => ['talent_type_ids', 'project_types', 'budget_tier']]);

    api()->withToken($this->token)->patchJson('/api/v1/brand/creative-needs', [
        'talent_type_ids' => [$type->id], 'project_types' => ['editorial'], 'project_frequency' => 'weekly', 'budget_tier' => '500_2000',
    ])->assertOk();

    api()->withToken($this->token)->getJson('/api/v1/brand/creative-needs')->assertOk()
        ->assertJsonPath('data.project_frequency', 'weekly')->assertJsonPath('data.budget_tier', '500_2000');
});

it('validates creative needs', function () {
    api()->withToken($this->token)->patchJson('/api/v1/brand/creative-needs', ['project_types' => ['nope']])
        ->assertStatus(422)->assertJsonValidationErrors('project_types.0');
});

// --- Reviews received (read-only) ------------------------------------------

it('lists only approved reviews received, paginated', function () {
    BrandReview::factory()->count(3)->for($this->brand)->create(['is_approved' => true, 'status' => 'approved']);
    BrandReview::factory()->pending()->for($this->brand)->create();

    api()->withToken($this->token)->getJson('/api/v1/brand/reviews')
        ->assertOk()->assertJsonPath('meta.pagination.total', 3);
});

// --- Credibility (read) ----------------------------------------------------

it('returns credibility (null then present)', function () {
    api()->withToken($this->token)->getJson('/api/v1/brand/credibility')->assertOk()->assertJsonPath('data', null);

    BrandCredibility::factory()->for($this->brand)->create(['completed_projects_count' => 4]);

    api()->withToken($this->token)->getJson('/api/v1/brand/credibility')
        ->assertOk()->assertJsonPath('data.completed_projects_count', 4);
});

// --- Account ---------------------------------------------------------------

it('reads and updates account settings-stage fields', function () {
    api()->withToken($this->token)->getJson('/api/v1/brand/account')->assertOk()
        ->assertJsonStructure(['data' => ['slug', 'company_size', 'is_published', 'status']]);

    api()->withToken($this->token)->patchJson('/api/v1/brand/account', [
        'slug' => 'nomad-coffee', 'company_size' => 'small', 'founded_year' => 2019,
    ])->assertOk()->assertJsonPath('data.slug', 'nomad-coffee');
});

it('rejects a duplicate slug but allows keeping own slug', function () {
    Brand::factory()->create(['slug' => 'taken']);

    api()->withToken($this->token)->patchJson('/api/v1/brand/account', ['slug' => 'taken'])
        ->assertStatus(422)->assertJsonValidationErrors('slug');
    api()->withToken($this->token)->patchJson('/api/v1/brand/account', ['slug' => $this->brand->slug])->assertOk();
});

it('unpublishes and publishes (publish requires completion)', function () {
    // A complete brand can toggle publish.
    $this->brand->update(['is_complete' => true, 'is_published' => false, 'status' => 'unpublished']);

    api()->withToken($this->token)->patchJson('/api/v1/brand/account/publish', ['publish' => true])
        ->assertOk()->assertJsonPath('data.is_published', true);

    // An incomplete brand is blocked (422).
    $this->brand->update(['is_complete' => false, 'is_published' => false, 'status' => 'unpublished']);
    api()->withToken($this->token)->patchJson('/api/v1/brand/account/publish', ['publish' => true])->assertStatus(422);
});
