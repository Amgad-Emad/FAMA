<?php

use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\Talent;
use App\Models\TalentType;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed(TalentTypeSeeder::class));

it('redirects an incomplete brand to the onboarding wizard', function () {
    $brand = Brand::factory()->incomplete()->create();

    $this->actingAs($brand, 'brand')
        ->get('/brand/dashboard')
        ->assertRedirect(route('brand.onboarding'));
});

it('renders the onboarding wizard for an incomplete brand', function () {
    $brand = Brand::factory()->incomplete()->create();

    $this->actingAs($brand, 'brand')->get('/brand/onboarding')->assertOk();
});

it('completes onboarding through the wizard endpoints and flips is_complete', function () {
    $brand = Brand::factory()->create(['status' => 'registered', 'is_complete' => false, 'is_published' => false]);
    $typeIds = TalentType::whereIn('slug', ['model', 'photographer'])->pluck('id')->all();

    $this->actingAs($brand, 'brand')->postJson('/brand/onboarding/identity', ['name' => 'Nomad', 'industry' => 'food_beverage', 'brand_stage' => 'growing'])->assertOk();
    $this->actingAs($brand, 'brand')->postJson('/brand/onboarding/location', ['base_city' => 'Cairo', 'base_country' => 'Egypt', 'geographic_reach' => 'mena'])->assertOk();
    $this->actingAs($brand, 'brand')->postJson('/brand/onboarding/creative-needs', ['talent_type_ids' => $typeIds, 'project_types' => ['lookbook'], 'project_frequency' => 'monthly'])->assertOk();
    $this->actingAs($brand, 'brand')->postJson('/brand/onboarding/aesthetic', ['mood_tags' => ['warm'], 'brand_references' => 'Kinfolk'])->assertOk();
    $this->actingAs($brand, 'brand')->postJson('/brand/onboarding/budget', ['budget_tier' => '2000_10000'])->assertOk();
    $this->actingAs($brand, 'brand')->postJson('/brand/onboarding/complete', [])->assertCreated();

    expect((bool) $brand->fresh()->is_complete)->toBeTrue();
    expect($brand->fresh()->status->getValue())->toBe('complete');
});

it('renders every dashboard page for a complete brand', function () {
    $brand = Brand::factory()->create();

    foreach (['dashboard', 'profile', 'creative-needs', 'campaigns', 'discover', 'deals', 'reviews', 'account'] as $page) {
        $this->actingAs($brand, 'brand')->get("/brand/{$page}")->assertOk();
    }
});

it('returns a personalised discovery feed and records a browse signal', function () {
    $brand = Brand::factory()->create(['geographic_reach' => 'mena']);
    $need = $brand->creativeNeed()->create([]);
    $photographer = TalentType::where('slug', 'photographer')->firstOrFail();
    $need->talentTypes()->attach($photographer->id);
    $talent = Talent::factory()->create();
    $talent->talentTypes()->attach($photographer->id, ['is_primary' => true, 'position' => 0]);

    $this->actingAs($brand, 'brand')
        ->getJson('/brand/discover/feed')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    expect($brand->signals()->where('action_type', 'view')->count())->toBeGreaterThan(0);
});

it('records a save signal from the feed', function () {
    $brand = Brand::factory()->create();
    $talent = Talent::factory()->create();

    $this->actingAs($brand, 'brand')
        ->postJson('/brand/discover/save', ['talent_id' => $talent->id])
        ->assertOk();

    expect($brand->signals()->where('action_type', 'save')->count())->toBe(1);
});

it('creates a campaign and opens it through the controller', function () {
    $brand = Brand::factory()->create();
    $model = TalentType::where('slug', 'model')->firstOrFail();

    $response = $this->actingAs($brand, 'brand')->postJson('/brand/campaigns', [
        'title' => 'Autumn Launch', 'type' => 'campaign',
        'roles' => [['talent_type_id' => $model->id, 'quantity' => 2]],
    ])->assertCreated();

    $id = $response->json('data.id');

    $this->actingAs($brand, 'brand')
        ->patchJson("/brand/campaigns/{$id}/status", ['action' => 'open'])
        ->assertOk()
        ->assertJsonPath('data.status', 'open');
});

it('exposes only approved reviews to the brand', function () {
    $brand = Brand::factory()->create();
    BrandReview::factory()->for($brand)->create(['is_approved' => true, 'status' => 'approved']);
    BrandReview::factory()->for($brand)->pending()->create();

    $this->actingAs($brand, 'brand')
        ->getJson('/brand/reviews/data')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
