<?php

use App\Models\Brand;
use App\Models\Talent;

/**
 * The public-profile "Message" CTA (ADR-P): interim brand↔talent messaging entry.
 * Guest → brand auth (profile preserved as the return URL); brand → "coming soon".
 */
it('redirects a guest who clicks Message to brand login, preserving the profile as the return URL', function () {
    Talent::factory()->create(['slug' => 'nadia', 'is_published' => true]);

    $this->get(route('brand.talents.message', ['talent' => 'nadia']))
        ->assertRedirect(route('login', ['role' => 'brand']))
        ->assertSessionHas('url.intended', route('talent.public', ['slug' => 'nadia']));
});

it('routes an authenticated brand to the interim messaging stub with a coming-soon flash', function () {
    $brand = Brand::factory()->create();
    Talent::factory()->create(['slug' => 'nadia', 'is_published' => true]);

    $this->actingAs($brand, 'brand')
        ->get(route('brand.talents.message', ['talent' => 'nadia']))
        ->assertRedirect(route('talent.public', ['slug' => 'nadia']))
        ->assertSessionHas('status');
});

it('404s the messaging route for an unknown talent slug', function () {
    $this->get(route('brand.talents.message', ['talent' => 'nobody']))->assertNotFound();
});
