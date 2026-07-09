<?php

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\TalentType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->token = $this->brand->createToken('t', ['brand'])->plainTextToken;
});

it('requires a brand token', function () {
    $this->getJson('/api/v1/brand/campaigns')->assertUnauthorized();
});

it('creates a campaign with roles and lists it paginated', function () {
    $type = TalentType::factory()->create();

    $id = api()->withToken($this->token)->postJson('/api/v1/brand/campaigns', [
        'title' => 'Summer 2026', 'budget_min' => 1000, 'budget_max' => 5000, 'currency' => 'EGP',
        'roles' => [['talent_type_id' => $type->id, 'quantity' => 2]],
    ])->assertCreated()->assertJsonPath('data.title', 'Summer 2026')->json('data.id');

    expect(Campaign::find($id)->talentTypes()->count())->toBe(1);

    Campaign::factory()->count(14)->for($this->brand)->create();
    api()->withToken($this->token)->getJson('/api/v1/brand/campaigns')
        ->assertOk()->assertJsonPath('meta.pagination.total', 15)->assertJsonPath('meta.pagination.per_page', 12);
});

it('validates campaign creation', function () {
    api()->withToken($this->token)->postJson('/api/v1/brand/campaigns', ['budget_min' => 100])
        ->assertStatus(422)->assertJsonValidationErrors('title');
});

it('shows a campaign with its deals and updates it', function () {
    $campaign = Campaign::factory()->for($this->brand)->create();

    api()->withToken($this->token)->getJson("/api/v1/brand/campaigns/{$campaign->id}")
        ->assertOk()->assertJsonStructure(['data' => ['campaign', 'deals']]);

    api()->withToken($this->token)->patchJson("/api/v1/brand/campaigns/{$campaign->id}", ['title' => 'Renamed'])
        ->assertOk()->assertJsonPath('data.title', 'Renamed');
});

it('transitions status and toggles public', function () {
    $campaign = Campaign::factory()->for($this->brand)->create(['status' => 'draft']);

    api()->withToken($this->token)->patchJson("/api/v1/brand/campaigns/{$campaign->id}/status", ['action' => 'open'])
        ->assertOk()->assertJsonPath('data.status', 'open');

    api()->withToken($this->token)->patchJson("/api/v1/brand/campaigns/{$campaign->id}/public", ['public' => true])
        ->assertOk()->assertJsonPath('data.is_public', true);
});

it('adds campaign media returning a url', function () {
    Storage::fake('public');
    $campaign = Campaign::factory()->for($this->brand)->create();

    api()->withToken($this->token)->post("/api/v1/brand/campaigns/{$campaign->id}/media", [
        'file' => UploadedFile::fake()->image('poster.jpg'),
    ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('success', true);
});

it('deletes a campaign', function () {
    $campaign = Campaign::factory()->for($this->brand)->create();

    api()->withToken($this->token)->deleteJson("/api/v1/brand/campaigns/{$campaign->id}")->assertOk();
    $this->assertSoftDeleted($campaign); // Campaign uses SoftDeletes
});

it('forbids acting on another brand’s campaign', function () {
    $foreign = Campaign::factory()->create();

    api()->withToken($this->token)->getJson("/api/v1/brand/campaigns/{$foreign->id}")->assertForbidden();
    api()->withToken($this->token)->patchJson("/api/v1/brand/campaigns/{$foreign->id}", ['title' => 'x'])->assertForbidden();
    api()->withToken($this->token)->deleteJson("/api/v1/brand/campaigns/{$foreign->id}")->assertForbidden();
});
