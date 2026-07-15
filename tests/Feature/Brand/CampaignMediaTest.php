<?php

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\CampaignMedia;
use Database\Seeders\TalentTypeSeeder;

beforeEach(fn () => $this->seed(TalentTypeSeeder::class));

it('removes a gallery item from the brand’s own campaign', function () {
    $brand = Brand::factory()->create();
    $campaign = Campaign::factory()->for($brand)->create();
    $media = $campaign->gallery()->create(['media_type' => 'image', 'position' => 0]);

    $this->actingAs($brand, 'brand')
        ->deleteJson("/brand/campaigns/{$campaign->id}/media/{$media->id}")
        ->assertOk();

    expect(CampaignMedia::find($media->id))->toBeNull();
});

it('404s removing a media item that belongs to a different campaign', function () {
    $brand = Brand::factory()->create();
    $campaign = Campaign::factory()->for($brand)->create();
    $other = Campaign::factory()->for($brand)->create();
    $media = $other->gallery()->create(['media_type' => 'image', 'position' => 0]);

    $this->actingAs($brand, 'brand')
        ->deleteJson("/brand/campaigns/{$campaign->id}/media/{$media->id}")
        ->assertNotFound();

    expect(CampaignMedia::find($media->id))->not->toBeNull();
});

it('forbids removing media from another brand’s campaign', function () {
    $owner = Brand::factory()->create();
    $intruder = Brand::factory()->create();
    $campaign = Campaign::factory()->for($owner)->create();
    $media = $campaign->gallery()->create(['media_type' => 'image', 'position' => 0]);

    $this->actingAs($intruder, 'brand')
        ->deleteJson("/brand/campaigns/{$campaign->id}/media/{$media->id}")
        ->assertForbidden();

    expect(CampaignMedia::find($media->id))->not->toBeNull();
});
