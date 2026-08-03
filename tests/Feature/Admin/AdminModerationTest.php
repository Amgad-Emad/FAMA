<?php

use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\BrandProject;
use App\Models\Review;
use App\Models\Talent;
use App\Models\TalentType;
use App\Models\User;
use App\Services\BrandModerationService;
use App\Services\ProjectOversightService;
use App\Services\SkillCatalogService;
use App\Services\ReviewModerationService;
use App\Services\TalentModerationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TalentTypeSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->powerless = User::factory()->create();
});

it('suspends, soft-deletes and restores a talent (audited)', function () {
    $talent = Talent::factory()->create(['status' => 'live', 'is_published' => true]);
    $svc = app(TalentModerationService::class);

    $svc->suspend($this->admin, $talent, 'spam');
    expect($talent->fresh()->status->getValue())->toBe('suspended');

    $svc->softDelete($this->admin, $talent);
    expect(Talent::withTrashed()->find($talent->id)->trashed())->toBeTrue();

    $svc->restore($this->admin, $talent);
    expect(Talent::find($talent->id))->not->toBeNull();

    $activity = Activity::inLog('moderation')->where('description', 'talent.suspended')->first();
    expect($activity)->not->toBeNull();
    expect((int) $activity->causer_id)->toBe($this->admin->id);
    expect(data_get($activity->properties, 'reason'))->toBe('spam');
});

it('verifies (one-way) and suspends a brand', function () {
    $brand = Brand::factory()->create();
    $svc = app(BrandModerationService::class);

    $svc->verify($this->admin, $brand);
    expect((bool) $brand->fresh()->is_verified)->toBeTrue();

    $svc->suspend($this->admin, $brand);
    expect($brand->fresh()->status->getValue())->toBe('suspended');
});

it('approves talent reviews in batch', function () {
    $reviews = Review::factory()->count(3)->pending()->create();

    $count = app(ReviewModerationService::class)->approveBatch($this->admin, $reviews->pluck('id')->all());

    expect($count)->toBe(3);
    expect(Review::where('is_approved', true)->count())->toBe(3);
    expect(Activity::inLog('moderation')->where('description', 'review.approved')->count())->toBe(3);
});

it('moderates a brand review', function () {
    $review = BrandReview::factory()->pending()->create();

    app(ReviewModerationService::class)->approveBrandReview($this->admin, $review);

    expect((bool) $review->fresh()->is_approved)->toBeTrue();
});

it('force-privates and cancels a project', function () {
    $project = BrandProject::factory()->create(['status' => 'open', 'is_public' => true]);
    $svc = app(ProjectOversightService::class);

    $svc->forcePrivate($this->admin, $project);
    expect((bool) $project->fresh()->is_public)->toBeFalse();

    $svc->makePublic($this->admin, $project);
    expect((bool) $project->fresh()->is_public)->toBeTrue();

    $svc->cancel($this->admin, $project, 'policy breach');
    expect($project->fresh()->status->getValue())->toBe('cancelled');
});

it('edits a talent-type default_blocks (future seeds only) and adds a skill', function () {
    $this->seed(TalentTypeSeeder::class);
    $type = TalentType::where('slug', 'modeling')->firstOrFail();
    $svc = app(SkillCatalogService::class);

    $svc->updateDefaultBlocks($this->admin, $type, ['hero', 'gallery']);
    expect($type->fresh()->default_blocks)->toBe(['hero', 'gallery']);

    $new = $svc->addSkill($this->admin, [
        'name' => ['en' => 'Voice Artist', 'ar' => 'فنان صوت'],
        'category' => 'creative',
        'default_blocks' => ['hero'],
    ]);
    expect($new->slug)->toBe('voice-artist');
    expect(TalentType::where('slug', 'voice-artist')->exists())->toBeTrue();

    $activity = Activity::inLog('catalog')->where('description', 'talent_type.default_blocks_updated')->first();
    expect($activity)->not->toBeNull();
    expect((int) $activity->causer_id)->toBe($this->admin->id);
});

it('denies moderation to an admin without moderate-content', function () {
    $talent = Talent::factory()->create(['status' => 'live']);

    expect(fn () => app(TalentModerationService::class)->suspend($this->powerless, $talent))
        ->toThrow(AuthorizationException::class);
});

it('denies catalog edits to an admin without manage-flows', function () {
    $this->seed(TalentTypeSeeder::class);
    $type = TalentType::where('slug', 'modeling')->firstOrFail();

    expect(fn () => app(SkillCatalogService::class)->updateDefaultBlocks($this->powerless, $type, ['hero']))
        ->toThrow(AuthorizationException::class);
});

// ---------------------------------------------------------------------------
// Detail drawers — every queue exposes a show endpoint with full details.
// ---------------------------------------------------------------------------

it('shows full talent detail for the moderation drawer', function () {
    $this->seed(TalentTypeSeeder::class);
    $talent = Talent::factory()->create(['display_name' => 'Drawer Talent', 'base_city' => 'Cairo']);

    $this->actingAs($this->admin, 'admin')->getJson("/admin/moderation/talents/{$talent->id}")
        ->assertOk()
        ->assertJsonPath('data.display_name', 'Drawer Talent')
        ->assertJsonPath('data.city', 'Cairo')
        ->assertJsonStructure(['data' => ['bio', 'skills', 'status', 'is_published', 'view_count', 'blocks_count', 'projects_count', 'reviews_count', 'created_at']]);
});

it('shows full brand detail including credibility', function () {
    $brand = Brand::factory()->create(['name' => 'Drawer Brand']);
    $brand->credibility()->create(['completed_projects_count' => 4, 'response_rate_pct' => 80]);

    $this->actingAs($this->admin, 'admin')->getJson("/admin/moderation/brands/{$brand->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Drawer Brand')
        ->assertJsonPath('data.completed_projects', 4)
        ->assertJsonStructure(['data' => ['description', 'industry', 'is_verified', 'is_published', 'projects_count', 'reviews_count']]);
});

it('shows full review and brand-review detail', function () {
    $review = Review::factory()->pending()->create(['body' => 'FULL_REVIEW_BODY', 'reviewer_name' => 'Jo']);
    $brandReview = BrandReview::factory()->pending()->create(['communication_rating' => 5, 'fairness_rating' => 4, 'creative_respect_rating' => 3]);

    $this->actingAs($this->admin, 'admin')->getJson("/admin/moderation/reviews/{$review->id}")
        ->assertOk()
        ->assertJsonPath('data.body', 'FULL_REVIEW_BODY')
        ->assertJsonPath('data.reviewer_name', 'Jo')
        ->assertJsonPath('data.kind', 'talent');

    $this->actingAs($this->admin, 'admin')->getJson("/admin/moderation/brand-reviews/{$brandReview->id}")
        ->assertOk()
        ->assertJsonPath('data.kind', 'brand')
        ->assertJsonPath('data.average_rating', 4)
        ->assertJsonStructure(['data' => ['communication_rating', 'fairness_rating', 'creative_respect_rating', 'brand', 'talent']]);
});

it('shows full project detail with the budget always visible to the admin', function () {
    $this->seed(TalentTypeSeeder::class);
    $project = BrandProject::factory()->create([
        'title' => 'Drawer Project', 'budget_min' => 5000, 'budget_max' => 9000,
        'currency' => 'EGP', 'budget_is_public' => false,
        'talent_type_id' => TalentType::where('slug', 'modeling')->first()->id,
    ]);

    $this->actingAs($this->admin, 'admin')->getJson("/admin/moderation/projects/{$project->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Drawer Project')
        ->assertJsonPath('data.budget_min', 5000)
        ->assertJsonPath('data.budget_is_public', false)
        ->assertJsonPath('data.role', 'Modeling');
});

it('denies the detail endpoints to a powerless admin', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($this->powerless, 'admin')->getJson("/admin/moderation/talents/{$talent->id}")->assertForbidden();
});

// ---------------------------------------------------------------------------
// State-aware toggles: publish ⇄ unpublish, suspend ⇄ reinstate.
// ---------------------------------------------------------------------------

it('toggles a talent between published and unpublished (audited)', function () {
    $talent = Talent::factory()->create(['status' => 'live', 'is_published' => true, 'display_name' => 'Toggle T']);

    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/talents/{$talent->id}/unpublish")->assertOk();
    expect($talent->fresh()->status->getValue())->toBe('unpublished');

    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/talents/{$talent->id}/publish")->assertOk();
    expect($talent->fresh()->status->getValue())->toBe('live');

    $logs = Activity::inLog('moderation')->pluck('description')->all();
    expect($logs)->toContain('talent.unpublished', 'talent.published');
});

it('reinstates a suspended talent back to a hidden state', function () {
    $talent = Talent::factory()->create(['status' => 'live', 'is_published' => true]);

    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/talents/{$talent->id}/suspend")->assertOk();
    expect($talent->fresh()->status->getValue())->toBe('suspended');

    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/talents/{$talent->id}/unsuspend")->assertOk();
    expect($talent->fresh()->status->getValue())->toBe('unpublished');
});

it('toggles a brand publish/unpublish and reinstates from suspended', function () {
    $brand = Brand::factory()->create(['status' => 'published', 'is_published' => true]);

    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/brands/{$brand->id}/unpublish")->assertOk();
    expect($brand->fresh()->status->getValue())->toBe('unpublished');
    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/brands/{$brand->id}/publish")->assertOk();
    expect($brand->fresh()->status->getValue())->toBe('published');

    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/brands/{$brand->id}/suspend")->assertOk();
    $this->actingAs($this->admin, 'admin')->patchJson("/admin/moderation/brands/{$brand->id}/unsuspend")->assertOk();
    expect($brand->fresh()->status->getValue())->toBe('unpublished');
});

// ---------------------------------------------------------------------------
// Search + status filters.
// ---------------------------------------------------------------------------

it('searches the talents queue by name and filters by status', function () {
    Talent::factory()->create(['display_name' => 'Findable Nova', 'status' => 'live', 'is_published' => true]);
    Talent::factory()->create(['display_name' => 'Someone Else', 'status' => 'draft', 'is_published' => false]);

    $found = $this->actingAs($this->admin, 'admin')->getJson('/admin/moderation/talents?q=Nova')->assertOk()->json('data');
    expect($found)->toHaveCount(1);
    expect($found[0]['display_name'])->toBe('Findable Nova');

    $draftOnly = $this->actingAs($this->admin, 'admin')->getJson('/admin/moderation/talents?status=draft')->assertOk()->json('data');
    expect(collect($draftOnly)->pluck('status')->unique()->all())->toBe(['draft']);
});

it('searches the brands queue by name', function () {
    Brand::factory()->create(['name' => 'Nomad Coffee Co']);
    Brand::factory()->create(['name' => 'Unrelated Studio']);

    $found = $this->actingAs($this->admin, 'admin')->getJson('/admin/moderation/brands?q=Nomad')->assertOk()->json('data');
    expect($found)->toHaveCount(1);
    expect($found[0]['name'])->toBe('Nomad Coffee Co');
});

// ---------------------------------------------------------------------------
// Contract system-event messages localize from their structured meta.
// ---------------------------------------------------------------------------

it('stores structured meta when a step completes', function () {
    $flow = App\Models\ContractFlow::factory()->create();
    $flow->steps()->createMany([
        ['key' => 'brief', 'name' => 'Project brief', 'actor' => 'brand', 'step_type' => 'form', 'position' => 0, 'is_required' => true, 'is_skippable' => false, 'settings' => ['fields' => ['scope']]],
        ['key' => 'quote', 'name' => 'Talent quote', 'actor' => 'talent', 'step_type' => 'upload', 'position' => 1, 'is_required' => true, 'is_skippable' => false, 'settings' => []],
    ]);
    $contract = app(App\Services\ContractService::class)->initiate([
        'brand_id' => Brand::factory()->create()->id,
        'talent_id' => Talent::factory()->create()->id,
        'title' => 'X', 'initiated_by' => 'brand',
    ], $flow);

    app(App\Services\ContractService::class)->advance($contract, ['fields' => ['scope' => 'a shoot']], 'brand');

    $event = $contract->messages()->where('type', 'system_event')->latest('id')->first();
    expect($event->meta['key'])->toBe('submitted');
    expect($event->meta['params']['step_key'])->toBe('brief');
});

it('localizes a system-event body from its meta in the current locale', function () {
    $contract = App\Models\Contract::factory()->create();
    $event = $contract->messages()->create([
        'sender_role' => 'system', 'type' => 'system_event', 'status' => 'sent',
        'body' => 'Brand submitted Project brief.',
        'meta' => ['key' => 'submitted', 'params' => ['actor' => 'brand', 'step_key' => 'brief', 'step_name' => 'Project brief']],
    ]);

    app()->setLocale('en');
    expect((new App\Http\Resources\ContractMessageResource($event))->toArray(request())['body'])
        ->toBe('Brand submitted Project brief.');

    app()->setLocale('ar');
    $ar = (new App\Http\Resources\ContractMessageResource($event))->toArray(request())['body'];
    expect($ar)->toContain('موجز المشروع')->toContain('علامة تجارية');
});
