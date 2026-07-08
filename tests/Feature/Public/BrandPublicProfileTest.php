<?php

use App\Models\Brand;
use App\Models\BrandReview;
use App\Models\Campaign;
use App\Models\TalentType;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed(TalentTypeSeeder::class));

it('renders a published brand profile with only approved reviews and public campaigns', function () {
    $brand = Brand::factory()->create(['slug' => 'nomad-coffee', 'name' => 'Nomad Coffee']);
    $brand->credibility()->create(['completed_projects_count' => 7, 'response_rate_pct' => 90]);

    BrandReview::factory()->for($brand)->create(['is_approved' => true, 'status' => 'approved', 'body' => 'APPROVED_BODY_XYZ']);
    BrandReview::factory()->for($brand)->pending()->create(['body' => 'PENDING_BODY_XYZ']);

    Campaign::factory()->for($brand)->create(['title' => 'Public Launch', 'slug' => 'public-launch', 'is_public' => true, 'status' => 'open']);
    Campaign::factory()->for($brand)->create(['title' => 'Secret Launch', 'slug' => 'secret-launch', 'is_public' => false, 'status' => 'draft']);

    $this->get('/brands/nomad-coffee')
        ->assertOk()
        ->assertSee('Nomad Coffee')
        ->assertSee('APPROVED_BODY_XYZ')
        ->assertDontSee('PENDING_BODY_XYZ')
        ->assertSee('Public Launch')
        ->assertDontSee('Secret Launch')
        ->assertSee('7'); // credibility completed_projects_count
});

it('404s an unpublished brand profile', function () {
    Brand::factory()->unpublished()->create(['slug' => 'hidden-brand']);

    $this->get('/brands/hidden-brand')->assertNotFound();
});

it('renders a public campaign detail with roles sought and gallery facts', function () {
    $brand = Brand::factory()->create(['slug' => 'nomad-coffee', 'name' => 'Nomad Coffee']);
    $campaign = Campaign::factory()->for($brand)->create([
        'title' => 'Autumn Menu', 'slug' => 'autumn-menu', 'is_public' => true, 'status' => 'open',
        'budget_min' => 10000, 'budget_max' => 40000, 'location_city' => 'Cairo', 'currency' => 'EGP',
    ]);
    $model = TalentType::where('slug', 'model')->firstOrFail();
    $campaign->talentTypes()->attach($model->id, ['quantity' => 3]);

    $this->get('/brands/nomad-coffee/campaigns/autumn-menu')
        ->assertOk()
        ->assertSee('Autumn Menu')
        ->assertSee('Cairo')
        ->assertSee('Model')   // role name
        ->assertSee('× 3');    // quantity
});

it('404s a private campaign detail', function () {
    $brand = Brand::factory()->create(['slug' => 'nomad-coffee']);
    Campaign::factory()->for($brand)->create(['slug' => 'hidden-campaign', 'is_public' => false, 'status' => 'draft']);

    $this->get('/brands/nomad-coffee/campaigns/hidden-campaign')->assertNotFound();
});

it('404s a campaign that belongs to a different brand (scoped binding)', function () {
    Brand::factory()->create(['slug' => 'brand-a']);
    $other = Brand::factory()->create(['slug' => 'brand-b']);
    Campaign::factory()->for($other)->create(['slug' => 'b-campaign', 'is_public' => true, 'status' => 'open']);

    $this->get('/brands/brand-a/campaigns/b-campaign')->assertNotFound();
});

it('404s a public campaign under an unpublished brand', function () {
    $brand = Brand::factory()->unpublished()->create(['slug' => 'hidden-brand']);
    Campaign::factory()->for($brand)->create(['slug' => 'a-campaign', 'is_public' => true, 'status' => 'open']);

    $this->get('/brands/hidden-brand/campaigns/a-campaign')->assertNotFound();
});
