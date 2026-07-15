<?php

use App\Models\Brand;
use App\Models\Talent;
use Database\Seeders\DealFlowSeeder;

/**
 * The public-profile / discovery "Message" CTA (ADR-P): the brand↔talent messaging
 * entry opens (or lazily starts) a deal and lands the brand in the deal room.
 * Guest → brand auth (returning to the message route).
 */
it('redirects a guest who clicks Message to brand login, returning to the message route', function () {
    Talent::factory()->create(['slug' => 'nadia', 'is_published' => true]);

    $this->get(route('brand.talents.message', ['talent' => 'nadia']))
        ->assertRedirect(route('login', ['role' => 'brand']))
        ->assertSessionHas('url.intended', route('brand.talents.message', ['talent' => 'nadia']));
});

it('starts a deal with the talent and lands the brand in the deal room', function () {
    $this->seed(DealFlowSeeder::class);
    $brand = Brand::factory()->create();
    $talent = Talent::factory()->create(['slug' => 'nadia', 'is_published' => true]);

    $response = $this->actingAs($brand, 'brand')
        ->get(route('brand.talents.message', ['talent' => 'nadia']));

    $deal = $brand->deals()->where('talent_id', $talent->id)->first();
    expect($deal)->not->toBeNull();
    $response->assertRedirect(route('brand.deals.show', $deal));
});

it('reuses the existing deal with the talent instead of creating a duplicate', function () {
    $this->seed(DealFlowSeeder::class);
    $brand = Brand::factory()->create();
    $talent = Talent::factory()->create(['slug' => 'nadia', 'is_published' => true]);

    $this->actingAs($brand, 'brand')->get(route('brand.talents.message', ['talent' => 'nadia']));
    $this->actingAs($brand, 'brand')->get(route('brand.talents.message', ['talent' => 'nadia']));

    expect($brand->deals()->where('talent_id', $talent->id)->count())->toBe(1);
});

it('404s the messaging route for an unknown talent slug', function () {
    $this->get(route('brand.talents.message', ['talent' => 'nobody']))->assertNotFound();
});
