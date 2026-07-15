<?php

use App\Models\Brand;
use App\Models\Talent;
use Database\Seeders\ContractFlowSeeder;

/**
 * The public-profile / discovery "Message" CTA (ADR-P): the brand↔talent messaging
 * entry opens (or lazily starts) a contract and lands the brand in the contract room.
 * Guest → brand auth (returning to the message route).
 */
it('redirects a guest who clicks Message to brand login, returning to the message route', function () {
    Talent::factory()->create(['slug' => 'nadia', 'is_published' => true]);

    $this->get(route('brand.talents.message', ['talent' => 'nadia']))
        ->assertRedirect(route('login', ['role' => 'brand']))
        ->assertSessionHas('url.intended', route('brand.talents.message', ['talent' => 'nadia']));
});

it('starts a contract with the talent and lands the brand in the contract room', function () {
    $this->seed(ContractFlowSeeder::class);
    $brand = Brand::factory()->create();
    $talent = Talent::factory()->create(['slug' => 'nadia', 'is_published' => true]);

    $response = $this->actingAs($brand, 'brand')
        ->get(route('brand.talents.message', ['talent' => 'nadia']));

    $contract = $brand->contracts()->where('talent_id', $talent->id)->first();
    expect($contract)->not->toBeNull();
    $response->assertRedirect(route('brand.contracts.show', $contract));
});

it('reuses the existing contract with the talent instead of creating a duplicate', function () {
    $this->seed(ContractFlowSeeder::class);
    $brand = Brand::factory()->create();
    $talent = Talent::factory()->create(['slug' => 'nadia', 'is_published' => true]);

    $this->actingAs($brand, 'brand')->get(route('brand.talents.message', ['talent' => 'nadia']));
    $this->actingAs($brand, 'brand')->get(route('brand.talents.message', ['talent' => 'nadia']));

    expect($brand->contracts()->where('talent_id', $talent->id)->count())->toBe(1);
});

it('404s the messaging route for an unknown talent slug', function () {
    $this->get(route('brand.talents.message', ['talent' => 'nobody']))->assertNotFound();
});
