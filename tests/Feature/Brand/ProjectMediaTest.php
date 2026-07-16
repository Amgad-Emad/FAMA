<?php

use App\Models\Brand;
use App\Models\BrandProject;
use App\Models\BrandProjectMedia;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed(TalentTypeSeeder::class));

it('removes a gallery item from the brand’s own campaign', function () {
    $brand = Brand::factory()->create();
    $campaign = BrandProject::factory()->for($brand)->create();
    $media = $campaign->gallery()->create(['media_type' => 'image', 'position' => 0]);

    $this->actingAs($brand, 'brand')
        ->deleteJson("/brand/projects/{$campaign->id}/media/{$media->id}")
        ->assertOk();

    expect(BrandProjectMedia::find($media->id))->toBeNull();
});

it('404s removing a media item that belongs to a different campaign', function () {
    $brand = Brand::factory()->create();
    $campaign = BrandProject::factory()->for($brand)->create();
    $other = BrandProject::factory()->for($brand)->create();
    $media = $other->gallery()->create(['media_type' => 'image', 'position' => 0]);

    $this->actingAs($brand, 'brand')
        ->deleteJson("/brand/projects/{$campaign->id}/media/{$media->id}")
        ->assertNotFound();

    expect(BrandProjectMedia::find($media->id))->not->toBeNull();
});

it('forbids removing media from another brand’s campaign', function () {
    $owner = Brand::factory()->create();
    $intruder = Brand::factory()->create();
    $campaign = BrandProject::factory()->for($owner)->create();
    $media = $campaign->gallery()->create(['media_type' => 'image', 'position' => 0]);

    $this->actingAs($intruder, 'brand')
        ->deleteJson("/brand/projects/{$campaign->id}/media/{$media->id}")
        ->assertForbidden();

    expect(BrandProjectMedia::find($media->id))->not->toBeNull();
});
