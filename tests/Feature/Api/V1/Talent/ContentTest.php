<?php

use App\Models\PortfolioItem;
use App\Models\Talent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->talent = Talent::factory()->create();
    $this->token = $this->talent->createToken('t', ['talent'])->plainTextToken;
});

it('requires a talent token', function () {
    $this->getJson('/api/v1/talent/content/gallery')->assertUnauthorized();
});

it('404s an unknown content type', function () {
    api()->withToken($this->token)->getJson('/api/v1/talent/content/nope')->assertNotFound();
});

it('creates, updates and serializes a gallery item with translatable maps', function () {
    $id = api()->withToken($this->token)->postJson('/api/v1/talent/content/gallery', [
        'caption' => ['en' => 'Backstage', 'ar' => 'كواليس'],
        'media_type' => 'image',
    ])->assertCreated()
        ->assertJsonPath('data.caption.en', 'Backstage')
        ->assertJsonPath('data.media_type', 'image')
        ->json('data.id');

    api()->withToken($this->token)->patchJson("/api/v1/talent/content/gallery/{$id}", [
        'caption' => ['en' => 'Updated'],
        'media_type' => 'video',
    ])->assertOk()->assertJsonPath('data.caption.en', 'Updated')->assertJsonPath('data.media_type', 'video');
});

it('validates content against the registry field kinds', function () {
    // look_types requires a name; media_type must be within the allowed set.
    api()->withToken($this->token)->postJson('/api/v1/talent/content/look_types', ['name' => ['en' => '']])
        ->assertStatus(422)->assertJsonValidationErrors('name.en');

    api()->withToken($this->token)->postJson('/api/v1/talent/content/gallery', ['media_type' => 'hologram'])
        ->assertStatus(422)->assertJsonValidationErrors('media_type');
});

it('paginates the content list', function () {
    PortfolioItem::factory()->count(30)->for($this->talent)->create();

    $res = api()->withToken($this->token)->getJson('/api/v1/talent/content/gallery')->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['pagination' => ['total', 'per_page']]]);

    expect($res->json('meta.pagination.total'))->toBe(30)
        ->and($res->json('meta.pagination.per_page'))->toBe(24)
        ->and(count($res->json('data')))->toBe(24);
});

it('reorders owned items and rejects foreign ids', function () {
    $a = PortfolioItem::factory()->for($this->talent)->create(['position' => 0]);
    $b = PortfolioItem::factory()->for($this->talent)->create(['position' => 1]);
    $foreign = PortfolioItem::factory()->create();

    api()->withToken($this->token)->patchJson('/api/v1/talent/content/gallery/reorder', ['order' => [$b->id, $a->id]])
        ->assertOk();
    expect($a->fresh()->position)->toBe(1)->and($b->fresh()->position)->toBe(0);

    api()->withToken($this->token)->patchJson('/api/v1/talent/content/gallery/reorder', ['order' => [$foreign->id]])
        ->assertForbidden();
});

it('uploads media and returns the conversion url', function () {
    Storage::fake('public');

    $id = api()->withToken($this->token)->postJson('/api/v1/talent/content/gallery', ['media_type' => 'image'])
        ->assertCreated()->json('data.id');

    api()->withToken($this->token)->post("/api/v1/talent/content/gallery/{$id}/media", [
        'file' => UploadedFile::fake()->image('shot.jpg', 800, 600),
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('success', true);

    expect(PortfolioItem::find($id)->getMedia('gallery'))->toHaveCount(1);
});

it('422s media upload on a type with no media collection', function () {
    $id = api()->withToken($this->token)->postJson('/api/v1/talent/content/equipment', ['name' => 'Tripod'])
        ->assertCreated()->json('data.id');

    api()->withToken($this->token)->post("/api/v1/talent/content/equipment/{$id}/media", [
        'file' => UploadedFile::fake()->image('x.jpg'),
    ], ['Accept' => 'application/json'])->assertStatus(422);
});

it('forbids managing another talent’s content item', function () {
    $foreign = PortfolioItem::factory()->create();

    api()->withToken($this->token)->patchJson("/api/v1/talent/content/gallery/{$foreign->id}", ['media_type' => 'image'])
        ->assertForbidden();
    api()->withToken($this->token)->deleteJson("/api/v1/talent/content/gallery/{$foreign->id}")
        ->assertForbidden();
});
