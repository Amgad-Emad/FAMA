<?php

use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\BrandProject;
use App\Models\Talent;
use App\Services\BrandOnboardingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// ---------------------------------------------------------------------------
// N+1 audits — query count stays flat as child collections grow.
// ---------------------------------------------------------------------------

it('renders the public brand profile with a flat query count (no N+1)', function () {
    $build = function (string $slug, int $n): string {
        $brand = Brand::factory()->create(['slug' => $slug, 'name' => strtoupper($slug)]);
        $brand->credibility()->create(['completed_projects_count' => 1, 'response_rate_pct' => 80]);
        for ($i = 0; $i < $n; $i++) {
            BrandProject::factory()->for($brand)->create(['slug' => "{$slug}-c{$i}", 'is_public' => true, 'status' => 'open']);
            $brand->images()->create(['position' => $i]);
            BrandReview::factory()->for($brand)->create(['is_approved' => true, 'status' => 'approved']);
        }

        return $slug;
    };
    $small = $build('np-small', 2);
    $big = $build('np-big', 7);

    $this->get("/brands/{$small}"); // warm up one-time queries

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get("/brands/{$small}")->assertOk();
    $qSmall = count(DB::getQueryLog());

    DB::flushQueryLog();
    $this->get("/brands/{$big}")->assertOk();
    $qBig = count(DB::getQueryLog());

    expect($qBig)->toBe($qSmall);
});

it('paginates the discovery feed without per-talent queries (no N+1)', function () {
    $this->seed(TalentTypeSeeder::class);
    $brand = Brand::factory()->create(['geographic_reach' => 'mena']); // no city narrowing
    $brand->creativeNeed()->create([]); // no type filter → all published talents
    Talent::factory()->count(10)->create();

    $this->actingAs($brand, 'brand')->getJson('/brand/discover/feed'); // warm up

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($brand, 'brand')->getJson('/brand/discover/feed')
        ->assertOk()
        ->assertJsonCount(10, 'data');

    // 10 talents rendered (types + media accessors) — a per-talent N+1 would be
    // well over 10 queries; the eager-loaded feed stays comfortably under.
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(10);
});

it('paginates the campaigns list with a flat query count (no N+1)', function () {
    $brand = Brand::factory()->create();
    BrandProject::factory()->count(2)->for($brand)->create();

    $this->actingAs($brand, 'brand')->getJson('/brand/projects/data'); // warm up

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($brand, 'brand')->getJson('/brand/projects/data')->assertOk();
    $qA = count(DB::getQueryLog());

    BrandProject::factory()->count(4)->for($brand)->create();
    DB::flushQueryLog();
    $this->actingAs($brand, 'brand')->getJson('/brand/projects/data')->assertOk();
    $qB = count(DB::getQueryLog());

    expect($qB)->toBe($qA);
});

// ---------------------------------------------------------------------------
// Transactions + fail-logging.
// ---------------------------------------------------------------------------

it('rolls back an onboarding step and fail-logs to the brands channel on error', function () {
    Log::shouldReceive('channel')->with('brands')->andReturnSelf();
    Log::shouldReceive('error')->once();

    $brand = Brand::factory()->create();

    expect(fn () => app(BrandOnboardingService::class)->creativeNeeds($brand, ['talent_type_ids' => [999999]]))
        ->toThrow(QueryException::class);

    // The updateOrCreate ran before the failing sync — the transaction rolled it back.
    expect($brand->creativeNeed()->exists())->toBeFalse();
});

// ---------------------------------------------------------------------------
// Publish gate + illegal transitions surface as 422.
// ---------------------------------------------------------------------------

it('refuses to publish before onboarding is complete (422)', function () {
    $brand = Brand::factory()->incomplete()->create();

    $this->actingAs($brand, 'brand')
        ->patchJson('/brand/account/publish', ['publish' => true])
        ->assertStatus(422);

    expect((bool) $brand->fresh()->is_published)->toBeFalse();
});

it('publishes then unpublishes a complete brand', function () {
    $brand = Brand::factory()->create(['status' => 'complete', 'is_published' => false]);

    $this->actingAs($brand, 'brand')->patchJson('/brand/account/publish', ['publish' => true])
        ->assertOk()->assertJsonPath('data.is_published', true);
    $this->actingAs($brand, 'brand')->patchJson('/brand/account/publish', ['publish' => false])
        ->assertOk()->assertJsonPath('data.is_published', false);
});

it('rejects an illegal campaign transition over HTTP (422)', function () {
    $this->seed(TalentTypeSeeder::class);
    $brand = Brand::factory()->create();
    $campaign = BrandProject::factory()->for($brand)->create(['status' => 'draft']);

    $this->actingAs($brand, 'brand')
        ->patchJson("/brand/projects/{$campaign->id}/status", ['action' => 'complete'])
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// Showcase scope + signal writes.
// ---------------------------------------------------------------------------

it('scopes the campaign showcase to completed public campaigns', function () {
    $brand = Brand::factory()->create();
    BrandProject::factory()->for($brand)->create(['is_public' => true, 'status' => 'completed', 'slug' => 'show-a']);
    BrandProject::factory()->for($brand)->create(['is_public' => false, 'status' => 'completed', 'slug' => 'show-b']);
    BrandProject::factory()->for($brand)->create(['is_public' => true, 'status' => 'open', 'slug' => 'show-c']);

    expect(BrandProject::showcase()->pluck('slug')->all())->toBe(['show-a']);
});

it('records a brief signal from the feed', function () {
    $brand = Brand::factory()->create();
    $talent = Talent::factory()->create();

    $this->actingAs($brand, 'brand')
        ->postJson('/brand/discover/brief', ['talent_id' => $talent->id])
        ->assertOk();

    expect($brand->signals()->where('action_type', 'brief_sent')->count())->toBe(1);
});
