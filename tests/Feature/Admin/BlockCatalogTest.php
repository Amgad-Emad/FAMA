<?php

use App\Models\BlockType;
use App\Models\BrandReview;
use App\Models\ProfileBlock;
use App\Models\Review;
use App\Models\Talent;
use App\Models\TalentType;
use App\Models\User;
use App\Services\BlockCatalogService;
use App\Services\ProfileBlockService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->moderator = User::factory()->create();
    $this->moderator->assignRole('moderator'); // no manage-blocks
});

// ---------------------------------------------------------------------------
// Block Catalog Manager — CRUD + eligibility gates.
// ---------------------------------------------------------------------------

it('lists the catalog with gates and usage counts', function () {
    BlockType::factory()->count(3)->create();

    $this->actingAs($this->admin, 'admin')->getJson('/admin/blocks/data')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => [['id', 'key', 'availability', 'categories', 'talent_type_ids', 'in_use_count']], 'meta']);
});

it('creates a block type with category gates and logs it', function () {
    $response = $this->actingAs($this->admin, 'admin')->postJson('/admin/blocks', [
        'key' => 'press_kit',
        'name' => ['en' => 'Press kit', 'ar' => 'ملف صحفي'],
        'availability' => 'by_category',
        'categories' => ['crew', 'creative'],
        'content_source' => 'inline',
        'default_layout' => 'list',
        'is_repeatable' => true,
        'settings_schema' => '{"fields": ["url"]}',
    ])->assertCreated();

    $blockType = BlockType::where('key', 'press_kit')->firstOrFail();
    expect($blockType->categories->pluck('category')->sort()->values()->all())->toBe(['creative', 'crew']);
    expect($blockType->settings_schema)->toBe(['fields' => ['url']]);

    $activity = Activity::inLog('catalog')->latest('id')->first();
    expect($activity->description)->toBe('block_type.created');
    expect((int) $activity->causer_id)->toBe($this->admin->id);
});

it('rejects a malformed settings_schema', function () {
    $this->actingAs($this->admin, 'admin')->postJson('/admin/blocks', [
        'key' => 'bad_schema', 'name' => ['en' => 'X'], 'availability' => 'universal',
        'content_source' => 'inline', 'settings_schema' => '{not json',
    ])->assertUnprocessable()->assertJsonValidationErrors(['settings_schema']);
});

it('switches availability and syncs the gates', function () {
    $this->seed(TalentTypeSeeder::class);
    $blockType = BlockType::factory()->create(['availability' => 'by_category']);
    $blockType->categories()->create(['category' => 'model']);
    $skill = TalentType::where('slug', 'photography')->firstOrFail();

    $this->actingAs($this->admin, 'admin')->patchJson("/admin/blocks/{$blockType->id}", [
        'availability' => 'by_type',
        'talent_type_ids' => [$skill->id],
    ])->assertOk();

    $blockType->refresh();
    expect($blockType->availability)->toBe('by_type');
    expect($blockType->categories)->toHaveCount(0);           // stale gates removed
    expect($blockType->talentTypes->pluck('id')->all())->toBe([$skill->id]);
});

it('locks key and content_source once the block is in use', function () {
    $this->seed(TalentTypeSeeder::class);
    $blockType = BlockType::factory()->create(['key' => 'gallery_x', 'content_source' => 'inline']);
    ProfileBlock::factory()->create(['block_type_id' => $blockType->id]);

    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/blocks/{$blockType->id}", ['key' => 'renamed'])
        ->assertUnprocessable();

    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/blocks/{$blockType->id}", ['content_source' => 'table'])
        ->assertUnprocessable();

    // An unused block may still be re-keyed.
    $fresh = BlockType::factory()->create(['key' => 'unused']);
    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/blocks/{$fresh->id}", ['key' => 'renamed_ok'])
        ->assertOk();
});

it('grandfathers a deactivated block: existing rows stay, new placements stop', function () {
    $this->seed(TalentTypeSeeder::class);
    $blockType = BlockType::factory()->create(['availability' => 'universal', 'is_active' => true]);
    $talent = Talent::factory()->create();
    $existing = ProfileBlock::factory()->create(['talent_id' => $talent->id, 'block_type_id' => $blockType->id, 'talent_type_id' => null]);

    $this->actingAs($this->admin, 'admin')->patchJson("/admin/blocks/{$blockType->id}/toggle")->assertOk();

    // The existing placement survives untouched…
    expect(ProfileBlock::whereKey($existing->id)->exists())->toBeTrue();
    // …but the talent-side picker no longer offers the type.
    $offered = app(ProfileBlockService::class)->availableBlockTypes($talent->fresh());
    expect($offered->pluck('id'))->not->toContain($blockType->id);
});

it('rejects a non-permitted admin at both layers', function () {
    $blockType = BlockType::factory()->create();

    // Layer 1: route middleware.
    $this->actingAs($this->moderator, 'admin')->getJson('/admin/blocks/data')->assertForbidden();
    $this->actingAs($this->moderator, 'admin')->patchJson("/admin/blocks/{$blockType->id}/toggle")->assertForbidden();

    // Layer 2: the service re-checks even if the route were misconfigured.
    expect(fn () => app(BlockCatalogService::class)->toggleActive($this->moderator, $blockType))
        ->toThrow(AuthorizationException::class);
});

// ---------------------------------------------------------------------------
// Skills Template Manager — preselection restricted to eligible blocks.
// ---------------------------------------------------------------------------

it('offers each skill only its eligible blocks and flags stale preselections', function () {
    $this->seed(TalentTypeSeeder::class);
    $modeling = TalentType::where('slug', 'modeling')->firstOrFail();

    $universal = BlockType::factory()->create(['key' => 'u_block', 'availability' => 'universal']);
    $crewOnly = BlockType::factory()->create(['key' => 'crew_block', 'availability' => 'by_category']);
    $crewOnly->categories()->create(['category' => 'crew']);
    $inactive = BlockType::factory()->create(['key' => 'retired_block', 'availability' => 'universal', 'is_active' => false]);

    // A stale preselection: modeling still lists the crew-gated + retired keys.
    $modeling->update(['default_blocks' => ['u_block', 'crew_block', 'retired_block']]);

    $data = $this->actingAs($this->admin, 'admin')->getJson('/admin/skills/data')->assertOk()->json('data.types');
    $row = collect($data)->firstWhere('slug', 'modeling');

    $eligibleKeys = collect($row['eligible_blocks'])->pluck('key');
    expect($eligibleKeys)->toContain('u_block');
    expect($eligibleKeys)->not->toContain('crew_block');
    expect($eligibleKeys)->not->toContain('retired_block');
    expect($row['invalid_blocks'])->toBe(['crew_block', 'retired_block']);
});

it('rejects ADDING an ineligible block but allows reordering around a stale one', function () {
    $this->seed(TalentTypeSeeder::class);
    $modeling = TalentType::where('slug', 'modeling')->firstOrFail();

    $universal = BlockType::factory()->create(['key' => 'u_block', 'availability' => 'universal']);
    $crewOnly = BlockType::factory()->create(['key' => 'crew_block', 'availability' => 'by_category']);
    $crewOnly->categories()->create(['category' => 'crew']);

    // Adding an ineligible key is refused (eligibility belongs to the catalog).
    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/skills/{$modeling->id}/blocks", ['default_blocks' => ['crew_block']])
        ->assertUnprocessable();

    // A key that was ALREADY preselected may stay / move / go while stale.
    $modeling->update(['default_blocks' => ['crew_block']]);
    $this->actingAs($this->admin, 'admin')
        ->patchJson("/admin/skills/{$modeling->id}/blocks", ['default_blocks' => ['u_block', 'crew_block']])
        ->assertOk();

    expect($modeling->fresh()->default_blocks)->toBe(['u_block', 'crew_block']);
});

// ---------------------------------------------------------------------------
// Global review queue — both kinds, one paginated list.
// ---------------------------------------------------------------------------

it('serves the global review queue with both kinds tagged', function () {
    Review::factory()->count(2)->pending()->create();
    BrandReview::factory()->count(3)->pending()->create();
    Review::factory()->create(['status' => 'approved', 'is_approved' => true]); // not pending → excluded

    $response = $this->actingAs($this->admin, 'admin')->getJson('/admin/moderation/all-reviews')->assertOk();
    $rows = collect($response->json('data'));

    expect($rows)->toHaveCount(5);
    expect($rows->pluck('kind')->unique()->sort()->values()->all())->toBe(['brand', 'talent']);
    expect($response->json('meta.pagination.total'))->toBe(5);
});
