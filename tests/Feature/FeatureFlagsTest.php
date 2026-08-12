<?php

use App\Actions\Feed\ToggleFollow;
use App\Models\Brand;
use App\Models\Talent;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

// V1 release scope: brands / contracts / messaging OFF. Flags default ON in code,
// so these are flipped per-test here to exercise the talent-only production build.
beforeEach(function () {
    config([
        'features.brands' => false,
        'features.contracts' => false,
        'features.messaging' => false,
        'feed.allow_following_brands' => false,
    ]);
});

it('404s the public brand + opportunities + messaging routes', function () {
    $brand = Brand::factory()->create();
    $talent = Talent::factory()->create();

    $this->get('/brands')->assertNotFound();
    $this->get('/brands/'.$brand->slug)->assertNotFound();
    $this->get('/projects')->assertNotFound();
    $this->get('/brand/talents/'.$talent->slug.'/message')->assertNotFound();
    $this->get('/'.$talent->slug.'/enquire')->assertNotFound();
});

it('404s the talent contracts routes for an authed talent', function () {
    $talent = Talent::factory()->create();

    $this->actingAs($talent, 'talent')->get('/talent/contracts')->assertNotFound();
});

it('404s the admin contract + flow consoles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin, 'admin')->get('/admin/contracts')->assertNotFound();
    $this->actingAs($admin, 'admin')->get('/admin/flows')->assertNotFound();
    $this->actingAs($admin, 'admin')->get('/admin/contract-templates')->assertNotFound();
    // Skills shares the manage-flows permission but stays enabled.
    $this->actingAs($admin, 'admin')->get('/admin/skills')->assertOk();
});

it('rejects brand registration and brand login', function () {
    $this->post('/register', [
        'account_type' => 'brand',
        'name' => 'B', 'email' => 'b@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasErrors('account_type');

    $this->from('/login')->post('/login', [
        'email' => 'x@example.com', 'password' => 'password', 'role' => 'brand',
    ])->assertSessionHasErrors('role');
});

it('serves the talent-only feed without the brands rail', function () {
    $this->get('/feed')->assertOk()->assertDontSee('BRANDS TO WATCH');
});

it('hides the Message CTA on the public profile but keeps Leave a review', function () {
    $talent = Talent::factory()->create();

    $this->get('/'.$talent->slug)
        ->assertOk()
        ->assertDontSee('brand/talents/'.$talent->slug.'/message')
        ->assertSee(__('Leave a review'));
});

it('hides the dark-mode toggle and forces light when dark mode is off', function () {
    config(['features.dark_mode' => false]);
    $talent = Talent::factory()->create();

    $this->get('/'.$talent->slug)
        ->assertOk()
        ->assertDontSee(__('Toggle theme'))
        ->assertSee("setAttribute('data-theme', 'light')", false);
});

it('refuses a talent→brand follow', function () {
    $talent = Talent::factory()->create();
    $brand = Brand::factory()->create();

    expect(fn () => app(ToggleFollow::class)($talent, $brand))
        ->toThrow(InvalidArgumentException::class);
});

it('falls back to the talents queue for a disabled moderation deep-link', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    // ?queue=brands is not a valid queue when brands are off → shell defaults to talents.
    $this->actingAs($admin, 'admin')->get('/admin/moderation?queue=brands')
        ->assertOk()->assertViewHas('queue', 'talents');
    // And the brand queue data route 404s outright.
    $this->actingAs($admin, 'admin')->get('/admin/moderation/brands')->assertNotFound();
});

it('404s public discovery when the discover feature is off', function () {
    config(['features.discover' => false]);

    $this->get('/discover')->assertNotFound();
    $this->get('/discover/search')->assertNotFound();
});

it('keeps public discovery reachable while the discover feature is on', function () {
    $this->withoutVite();

    $this->get('/discover')->assertOk();
});

it('hides the pricing rate when the pricing feature is off', function () {
    config(['features.pricing' => false]);
    $talent = Talent::factory()->create(['rate_unit' => 'day', 'rate_amount' => 100, 'rate_currency' => 'EGP']);

    $this->actingAs($talent, 'talent')->withoutVite();
    $this->get(route('talent.profile.edit'))->assertOk()->assertDontSee(__('Pricing rate'));
    $this->patch(route('talent.profile.pricing'), ['rate_unit' => 'day', 'rate_amount' => 50, 'rate_currency' => 'EGP'])->assertNotFound();
    $this->get('/'.$talent->slug)->assertOk()->assertDontSee(__('From').' EGP');
});
