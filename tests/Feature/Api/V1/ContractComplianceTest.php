<?php

use App\Models\AgencyAffiliation;
use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\Campaign;
use App\Models\Deal;
use App\Models\DealEnquiry;
use App\Models\PortfolioItem;
use App\Models\PressFeature;
use App\Models\Review;
use App\Models\Service;
use App\Models\Talent;
use App\Models\User;
use App\Notifications\DealTurnChanged;
use Database\Seeders\RolesAndPermissionsSeeder;

/**
 * The handoff contract guarantee: every paginated list endpoint returns the
 * shared envelope with a complete `meta.pagination`, and — because these run with
 * preventLazyLoading on and non-empty data — none of them lazy-loads (an N+1 would
 * throw here). One test per list keeps a regression pinned to its endpoint.
 */

// Assert the full envelope shape + a complete pagination block on a non-empty list.
function assertPaginated($response): void
{
    $response->assertOk()
        ->assertJsonStructure([
            'success', 'data', 'message', 'errors',
            'meta' => ['pagination' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to']],
        ])
        ->assertJsonPath('success', true);

    expect($response->json('data'))->toBeArray()
        ->and($response->json('errors'))->toBeNull()
        ->and($response->json('meta.pagination.total'))->toBeGreaterThan(0)
        ->and($response->json('meta.pagination.per_page'))->toBeGreaterThan(0);
}

function talentTok(Talent $t): string
{
    return $t->createToken('t', ['talent'])->plainTextToken;
}

function brandTok(Brand $b): string
{
    return $b->createToken('t', ['brand'])->plainTextToken;
}

// ---------------------------------------------------------------------------
// Public paginated lists.
// ---------------------------------------------------------------------------

it('talents search — envelope + pagination', function () {
    Talent::factory()->count(2)->create(['is_published' => true, 'status' => 'live']);
    assertPaginated($this->getJson('/api/v1/talents'));
});

it('brands directory — envelope + pagination', function () {
    Brand::factory()->count(2)->create(['is_published' => true, 'status' => 'published']);
    assertPaginated($this->getJson('/api/v1/brands'));
});

// ---------------------------------------------------------------------------
// Talent workspace paginated lists.
// ---------------------------------------------------------------------------

it('talent lists — envelope + pagination on every one', function () {
    $talent = Talent::factory()->create();
    $token = talentTok($talent);

    Service::factory()->count(2)->for($talent)->create();
    Review::factory()->count(2)->for($talent)->create();
    AgencyAffiliation::factory()->count(2)->for($talent)->create();
    PressFeature::factory()->count(2)->for($talent)->create();
    DealEnquiry::factory()->count(2)->for($talent)->create();
    Deal::factory()->count(2)->create(['talent_id' => $talent->id]);
    PortfolioItem::factory()->count(2)->for($talent)->create();

    foreach ([
        '/api/v1/talent/services',
        '/api/v1/talent/reviews',
        '/api/v1/talent/affiliations',
        '/api/v1/talent/press',
        '/api/v1/talent/enquiries',
        '/api/v1/talent/deals',
        '/api/v1/talent/content/gallery',
    ] as $url) {
        assertPaginated(api()->withToken($token)->getJson($url));
    }
});

// ---------------------------------------------------------------------------
// Brand workspace paginated lists.
// ---------------------------------------------------------------------------

it('brand lists — envelope + pagination on every one', function () {
    $brand = Brand::factory()->create(['geographic_reach' => 'mena']);
    $token = brandTok($brand);

    Campaign::factory()->count(2)->for($brand)->create();
    BrandReview::factory()->count(2)->for($brand)->create(['is_approved' => true, 'status' => 'approved']);
    Deal::factory()->count(2)->create(['brand_id' => $brand->id]);
    Talent::factory()->count(2)->create(['is_published' => true, 'status' => 'live']); // feed

    foreach ([
        '/api/v1/brand/campaigns',
        '/api/v1/brand/reviews',
        '/api/v1/brand/deals',
        '/api/v1/brand/discover',
    ] as $url) {
        assertPaginated(api()->withToken($token)->getJson($url));
    }
});

// ---------------------------------------------------------------------------
// Cross-entity + admin paginated lists.
// ---------------------------------------------------------------------------

it('cross-entity deal inbox — envelope + pagination', function () {
    $talent = Talent::factory()->create();
    Deal::factory()->count(2)->create(['talent_id' => $talent->id]);

    assertPaginated(api()->withToken(talentTok($talent))->getJson('/api/v1/deals'));
});

it('notifications — envelope + pagination', function () {
    $talent = Talent::factory()->create();
    $talent->notify(new DealTurnChanged(Deal::factory()->create(['talent_id' => $talent->id]), 'talent'));
    $talent->notify(new DealTurnChanged(Deal::factory()->create(['talent_id' => $talent->id]), 'talent'));

    assertPaginated(api()->withToken(talentTok($talent))->getJson('/api/v1/notifications'));
});

it('admin activity — envelope + pagination', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    activity('moderation')->causedBy($admin)->performedOn(Brand::factory()->create())->log('brand.verified');
    activity('moderation')->causedBy($admin)->performedOn(Brand::factory()->create())->log('brand.verified');

    $abilities = array_values(array_unique(['admin', ...$admin->getAllPermissions()->pluck('name')->all()]));
    assertPaginated(api()->withToken($admin->createToken('t', $abilities)->plainTextToken)->getJson('/api/v1/admin/activity'));
});

// ---------------------------------------------------------------------------
// Bounded collection + single-resource endpoints still return the envelope
// (they intentionally return the whole set — documented in docs/api.md).
// ---------------------------------------------------------------------------

it('bounded collections return the envelope (data present, no pagination by design)', function () {
    $talent = Talent::factory()->create();

    foreach ([
        '/api/v1/lookups/talent-types',
        '/api/v1/lookups/block-types',
        '/api/v1/lookups/deal-flows',
        '/api/v1/lookups/options',
    ] as $url) {
        $this->getJson($url)->assertOk()
            ->assertJsonStructure(['success', 'data', 'message', 'errors', 'meta'])
            ->assertJsonPath('success', true);
    }

    foreach ([
        '/api/v1/talent/profile/blocks',
        '/api/v1/talent/professions',
    ] as $url) {
        api()->withToken(talentTok($talent))->getJson($url)->assertOk()
            ->assertJsonStructure(['success', 'data', 'message', 'errors', 'meta'])
            ->assertJsonPath('success', true);
    }
});
