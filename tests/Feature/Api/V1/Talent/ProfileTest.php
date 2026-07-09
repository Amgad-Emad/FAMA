<?php

use App\Models\BlockType;
use App\Models\Brand;
use App\Models\ProfileBlock;
use App\Models\Talent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->talent = Talent::factory()->create();
    $this->token = $this->talent->createToken('t', ['talent'])->plainTextToken;
});

it('requires a talent token', function () {
    $this->getJson('/api/v1/talent/profile')->assertUnauthorized();
});

it('rejects a brand token (wrong ability)', function () {
    $brandToken = Brand::factory()->create()->createToken('t', ['brand'])->plainTextToken;

    api()->withToken($brandToken)->getJson('/api/v1/talent/profile')->assertForbidden();
});

it('returns the own profile with translatable maps and blocks', function () {
    $this->talent->setTranslation('headline', 'en', 'Photographer')->setTranslation('headline', 'ar', 'مصوّر')->save();

    api()->withToken($this->token)->getJson('/api/v1/talent/profile')
        ->assertOk()
        ->assertJsonPath('data.id', $this->talent->id)
        ->assertJsonPath('data.headline.en', 'Photographer')
        ->assertJsonPath('data.headline.ar', 'مصوّر')
        ->assertJsonStructure(['data' => ['slug', 'talent_types', 'blocks', 'availability_status']]);
});

it('updates the core profile', function () {
    api()->withToken($this->token)->patchJson('/api/v1/talent/profile', [
        'display_name' => 'Amgad',
        'headline' => ['en' => 'DP', 'ar' => 'مدير تصوير'],
        'base_city' => 'Cairo',
    ])->assertOk()->assertJsonPath('data.display_name', 'Amgad')->assertJsonPath('data.base_city', 'Cairo');

    expect($this->talent->fresh()->getTranslation('headline', 'ar'))->toBe('مدير تصوير');
});

it('validates the core profile update', function () {
    api()->withToken($this->token)->patchJson('/api/v1/talent/profile', [
        'booking_type' => 'telepathy', // not in the allowed set
    ])->assertStatus(422)->assertJsonValidationErrors('booking_type');
});

it('uploads a hero image and returns its url', function () {
    Storage::fake('public');

    api()->withToken($this->token)->post('/api/v1/talent/profile/hero', [
        'image' => UploadedFile::fake()->image('hero.jpg', 1200, 800),
    ], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($this->talent->fresh()->getFirstMedia('hero'))->not->toBeNull();
});

it('adds, fills, reorders, hides and removes profile blocks', function () {
    $type = BlockType::factory()->create(['availability' => 'universal', 'is_repeatable' => true, 'content_source' => 'inline']);

    $id = api()->withToken($this->token)->postJson('/api/v1/talent/profile/blocks', ['block_type_id' => $type->id])
        ->assertCreated()->json('data.id');

    api()->withToken($this->token)->patchJson("/api/v1/talent/profile/blocks/{$id}", ['title' => ['en' => 'Gallery'], 'is_visible' => true])
        ->assertOk()->assertJsonPath('data.title.en', 'Gallery');

    api()->withToken($this->token)->patchJson("/api/v1/talent/profile/blocks/{$id}/visibility", ['is_visible' => false])
        ->assertOk()->assertJsonPath('data.is_visible', false);

    api()->withToken($this->token)->patchJson('/api/v1/talent/profile/blocks/reorder', ['order' => [$id]])->assertOk();

    api()->withToken($this->token)->deleteJson("/api/v1/talent/profile/blocks/{$id}")->assertOk();
    $this->assertDatabaseMissing('profile_blocks', ['id' => $id]);
});

it('forbids filling another talent’s block', function () {
    $foreign = ProfileBlock::factory()->create(); // owned by its own (foreign) talent

    api()->withToken($this->token)->patchJson("/api/v1/talent/profile/blocks/{$foreign->id}", ['title' => ['en' => 'x']])
        ->assertForbidden();
});

it('lists the eligibility-filtered block picker', function () {
    BlockType::factory()->create(['availability' => 'universal', 'is_active' => true]);

    api()->withToken($this->token)->getJson('/api/v1/talent/profile/block-picker')
        ->assertOk()->assertJsonPath('success', true);
});
