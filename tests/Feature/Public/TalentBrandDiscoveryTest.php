<?php

use App\Models\Brand;
use App\Models\BrandProject;
use App\Models\Contract;
use App\Models\Talent;
use Database\Seeders\ContractFlowSeeder;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed(TalentTypeSeeder::class));

// --- Brand discovery (talent-facing) -------------------------------------
it('renders the brand discovery page', function () {
    $this->get('/brands')->assertOk();
});

it('lists only published brands in the discovery feed', function () {
    Brand::factory()->create(['name' => 'Nomad Coffee']);
    Brand::factory()->unpublished()->create(['name' => 'Secret Co']);

    $this->getJson('/brands/feed')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Nomad Coffee');
});

it('filters the brand feed by industry, stage, reach, and verified', function () {
    Brand::factory()->create(['name' => 'Fashion House', 'industry' => 'fashion', 'brand_stage' => 'growing', 'geographic_reach' => 'mena']);
    Brand::factory()->create(['name' => 'Tech Co', 'industry' => 'tech', 'brand_stage' => 'new', 'geographic_reach' => 'same_city']);
    // Pin every facet so the single-result assertions below never collide with random data.
    Brand::factory()->verified()->create(['name' => 'Verified Beauty', 'industry' => 'beauty', 'brand_stage' => 'established', 'geographic_reach' => 'international']);

    $this->getJson('/brands/feed?industry=fashion')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Fashion House');
    $this->getJson('/brands/feed?brand_stage=new')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Tech Co');
    $this->getJson('/brands/feed?geographic_reach=mena')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Fashion House');
    $this->getJson('/brands/feed?verified=1')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Verified Beauty');
});

// --- Campaign browsing (talent-facing opportunities) ---------------------
it('renders the opportunities page', function () {
    $this->get('/projects')->assertOk();
});

it('lists only public, open campaigns from published brands', function () {
    $brand = Brand::factory()->create();
    BrandProject::factory()->for($brand)->open()->create(['title' => 'Autumn Launch', 'is_public' => true]);
    BrandProject::factory()->for($brand)->create(['title' => 'Draft Idea', 'is_public' => true]); // draft → excluded
    BrandProject::factory()->for($brand)->open()->create(['title' => 'Private Push', 'is_public' => false]); // private → excluded

    $hiddenBrand = Brand::factory()->unpublished()->create();
    BrandProject::factory()->for($hiddenBrand)->open()->create(['title' => 'Hidden Brand Camp', 'is_public' => true]); // hidden brand → excluded

    $this->getJson('/projects/feed')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Autumn Launch');
});

it('filters the campaign feed by discipline, type, and budget', function () {
    $brand = Brand::factory()->create();
    $photography = App\Models\TalentType::where('slug', 'photography')->firstOrFail();
    $modeling = App\Models\TalentType::where('slug', 'modeling')->firstOrFail();

    BrandProject::factory()->for($brand)->open()->create(['title' => 'Photo Shoot', 'type' => 'shoot', 'is_public' => true, 'budget_min' => 5000, 'budget_max' => 12000, 'talent_type_id' => $photography->id]);
    BrandProject::factory()->for($brand)->open()->create(['title' => 'Model Campaign', 'type' => 'campaign', 'is_public' => true, 'budget_min' => 40000, 'budget_max' => 80000, 'talent_type_id' => $modeling->id]);

    // By discipline (talent_type slug).
    $this->getJson('/projects/feed?type=photography')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Photo Shoot');
    // By campaign type.
    $this->getJson('/projects/feed?campaign_type=campaign')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Model Campaign');
    // By budget ceiling (campaigns that can pay at most 20k → the 5–12k shoot overlaps).
    $this->getJson('/projects/feed?budget_max=20000')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Photo Shoot');
});

// --- Talent → brand messaging (mirror of brand → talent, ADR-P) ----------
it('redirects a guest who clicks Message brand to talent login, keeping the return URL', function () {
    Brand::factory()->create(['slug' => 'nomad']);

    $this->get(route('brand.message', ['brand' => 'nomad']))
        ->assertRedirect(route('login', ['role' => 'talent']))
        ->assertSessionHas('url.intended');
});

it('starts a talent-initiated contract with the brand and lands the talent in the contract room', function () {
    $this->seed(ContractFlowSeeder::class);
    $brand = Brand::factory()->create(['slug' => 'nomad']);
    $talent = Talent::factory()->create();

    $response = $this->actingAs($talent, 'talent')->get(route('brand.message', ['brand' => 'nomad']));

    $contract = Contract::where('brand_id', $brand->id)->where('talent_id', $talent->id)->first();
    expect($contract)->not->toBeNull();
    expect($contract->initiated_by)->toBe('talent');
    $response->assertRedirect(route('talent.contracts.show', $contract));
});

it('tags the contract to the campaign the talent messaged about', function () {
    $this->seed(ContractFlowSeeder::class);
    $brand = Brand::factory()->create(['slug' => 'nomad']);
    $campaign = BrandProject::factory()->for($brand)->open()->create();
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->get(route('brand.message', ['brand' => 'nomad', 'project' => $campaign->id]));

    $contract = Contract::where('brand_id', $brand->id)->where('talent_id', $talent->id)->first();
    expect($contract->brand_project_id)->toBe($campaign->id);
});

it('reuses the existing contract with the brand instead of creating a duplicate', function () {
    $this->seed(ContractFlowSeeder::class);
    $brand = Brand::factory()->create(['slug' => 'nomad']);
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->get(route('brand.message', ['brand' => 'nomad']));
    $this->actingAs($talent, 'talent')->get(route('brand.message', ['brand' => 'nomad']));

    expect(Contract::where('brand_id', $brand->id)->where('talent_id', $talent->id)->count())->toBe(1);
});

it('404s the brand messaging route for an unpublished brand', function () {
    Brand::factory()->unpublished()->create(['slug' => 'hidden']);

    $this->actingAs(Talent::factory()->create(), 'talent')
        ->get(route('brand.message', ['brand' => 'hidden']))
        ->assertNotFound();
});
