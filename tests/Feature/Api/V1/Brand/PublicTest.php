<?php

use App\Models\Brand;
use App\Models\Campaign;

// Public brand reads — profile + public campaign detail.

it('shows a published brand profile with satellites and 404s an unpublished one', function () {
    $brand = Brand::factory()->create(['is_published' => true, 'status' => 'published']);
    $brand->setTranslation('description', 'en', 'Coffee')->setTranslation('description', 'ar', 'قهوة')->save();
    $hidden = Brand::factory()->unpublished()->create();

    $this->getJson("/api/v1/brands/{$brand->slug}")
        ->assertOk()
        ->assertJsonPath('data.slug', $brand->slug)
        ->assertJsonStructure(['data' => ['credibility', 'aesthetic', 'social_handles', 'images', 'reviews', 'campaigns']]);

    $this->getJson("/api/v1/brands/{$hidden->slug}")->assertNotFound();
});

it('returns the brand description in the Accept-Language locale', function () {
    $brand = Brand::factory()->create(['is_published' => true, 'status' => 'published']);
    $brand->setTranslation('description', 'en', 'Coffee')->setTranslation('description', 'ar', 'قهوة')->save();

    $this->withHeaders(['Accept-Language' => 'ar'])->getJson("/api/v1/brands/{$brand->slug}")
        ->assertOk()->assertJsonPath('data.description', 'قهوة');
});

it('shows a public campaign scoped to the brand and 404s a private one', function () {
    $brand = Brand::factory()->create(['is_published' => true, 'status' => 'published']);
    $public = Campaign::factory()->for($brand)->create(['is_public' => true, 'status' => 'open']);
    $public->setTranslation('description', 'en', 'A summer shoot')->setTranslation('description', 'ar', 'تصوير صيفي')->save();
    $private = Campaign::factory()->for($brand)->create(['is_public' => false, 'status' => 'draft']);

    // Public campaign description resolves to the Accept-Language locale (single string, not a map).
    $this->withHeaders(['Accept-Language' => 'ar'])->getJson("/api/v1/brands/{$brand->slug}/campaigns/{$public->slug}")
        ->assertOk()
        ->assertJsonPath('data.slug', $public->slug)
        ->assertJsonPath('data.description', 'تصوير صيفي');

    $this->getJson("/api/v1/brands/{$brand->slug}/campaigns/{$private->slug}")->assertNotFound();
});
