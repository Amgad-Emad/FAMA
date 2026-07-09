<?php

use App\Models\Brand;
use App\Models\BrandImage;
use App\Models\BrandSocialHandle;
use App\Models\Talent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    $this->token = $this->brand->createToken('t', ['brand'])->plainTextToken;
});

it('requires a brand token', function () {
    $this->getJson('/api/v1/brand/profile')->assertUnauthorized();
});

it('rejects a talent token (wrong ability)', function () {
    $talentToken = Talent::factory()->create()->createToken('t', ['talent'])->plainTextToken;

    api()->withToken($talentToken)->getJson('/api/v1/brand/profile')->assertForbidden();
});

it('returns the own profile with translatable maps + satellites', function () {
    $this->brand->setTranslation('description', 'en', 'Coffee brand')->setTranslation('description', 'ar', 'علامة قهوة')->save();

    api()->withToken($this->token)->getJson('/api/v1/brand/profile')
        ->assertOk()
        ->assertJsonPath('data.description.en', 'Coffee brand')
        ->assertJsonPath('data.description.ar', 'علامة قهوة')
        ->assertJsonStructure(['data' => ['aesthetic', 'social_handles', 'images']]);
});

it('updates the core profile and validates enums', function () {
    api()->withToken($this->token)->patchJson('/api/v1/brand/profile', [
        'name' => 'Nomad Coffee', 'industry' => 'food_beverage', 'base_city' => 'Cairo',
    ])->assertOk();
    expect($this->brand->fresh()->industry)->toBe('food_beverage');

    api()->withToken($this->token)->patchJson('/api/v1/brand/profile', ['name' => 'X', 'industry' => 'aliens'])
        ->assertStatus(422)->assertJsonValidationErrors('industry');
});

it('uploads a logo and a cover, returning urls', function () {
    Storage::fake('public');

    api()->withToken($this->token)->post('/api/v1/brand/profile/logo', [
        'file' => UploadedFile::fake()->image('logo.png', 400, 400),
    ], ['Accept' => 'application/json'])->assertOk();

    api()->withToken($this->token)->post('/api/v1/brand/profile/cover', [
        'file' => UploadedFile::fake()->image('cover.jpg', 1600, 600),
    ], ['Accept' => 'application/json'])->assertOk();

    expect($this->brand->fresh()->getFirstMedia('logo'))->not->toBeNull();
    expect($this->brand->fresh()->getFirstMedia('cover'))->not->toBeNull();
});

it('updates the aesthetic (references + mood tags)', function () {
    api()->withToken($this->token)->patchJson('/api/v1/brand/profile/aesthetic', [
        'brand_references' => 'Kinfolk, Cereal', 'mood_tags' => ['minimal', 'warm'],
    ])->assertOk();

    api()->withToken($this->token)->getJson('/api/v1/brand/profile')
        ->assertOk()->assertJsonPath('data.aesthetic.brand_references', 'Kinfolk, Cereal');
});

it('manages the image gallery', function () {
    Storage::fake('public');

    $id = api()->withToken($this->token)->post('/api/v1/brand/profile/images', [
        'file' => UploadedFile::fake()->image('shot.jpg'),
    ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');

    api()->withToken($this->token)->getJson('/api/v1/brand/profile/images')->assertOk()
        ->assertJsonCount(1, 'data.images');

    api()->withToken($this->token)->deleteJson("/api/v1/brand/profile/images/{$id}")->assertOk();
});

it('manages social handles and validates the platform', function () {
    api()->withToken($this->token)->postJson('/api/v1/brand/social', ['platform' => 'myspace', 'handle' => 'x'])
        ->assertStatus(422)->assertJsonValidationErrors('platform');

    $id = api()->withToken($this->token)->postJson('/api/v1/brand/social', [
        'platform' => 'instagram', 'handle' => '@nomad', 'url' => 'https://instagram.com/nomad',
    ])->assertCreated()->json('data.id');

    api()->withToken($this->token)->getJson('/api/v1/brand/social')->assertOk()->assertJsonCount(1, 'data.handles');
    api()->withToken($this->token)->deleteJson("/api/v1/brand/social/{$id}")->assertOk();
});

it('forbids removing another brand’s image or handle', function () {
    $foreignImage = BrandImage::factory()->create();
    $foreignHandle = BrandSocialHandle::factory()->create();

    api()->withToken($this->token)->deleteJson("/api/v1/brand/profile/images/{$foreignImage->id}")->assertForbidden();
    api()->withToken($this->token)->deleteJson("/api/v1/brand/social/{$foreignHandle->id}")->assertForbidden();
});
