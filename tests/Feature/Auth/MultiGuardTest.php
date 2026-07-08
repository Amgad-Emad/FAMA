<?php

use App\Models\Brand;
use App\Models\Talent;
use App\Models\User;

it('configures three guards mapped to providers and models', function () {
    expect(config('auth.guards.admin.provider'))->toBe('users');
    expect(config('auth.guards.brand.provider'))->toBe('brands');
    expect(config('auth.guards.talent.provider'))->toBe('talents');

    expect(config('auth.providers.users.model'))->toBe(User::class);
    expect(config('auth.providers.brands.model'))->toBe(Brand::class);
    expect(config('auth.providers.talents.model'))->toBe(Talent::class);
});

it('protects each dashboard behind its own guard', function () {
    $this->get('/admin/dashboard')->assertRedirect(route('login'));
    $this->get('/brand/dashboard')->assertRedirect(route('login'));
    $this->get('/talent/dashboard')->assertRedirect(route('login'));
});

it('lets an authenticated admin reach the admin dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'admin')->get('/admin/dashboard')->assertOk();
});

it('shows the role selector on the single login screen', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('name="role"', false);
});

it('authenticates on the guard chosen by the submitted role', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'role' => 'admin',
    ]);

    $this->assertAuthenticated('admin');
});

it('switching login to another guard replaces the active identity (no stale session wins)', function () {
    $brand = Brand::factory()->create();
    $talent = Talent::factory()->create();

    // Sign in as the brand first.
    $this->post('/login', ['email' => $brand->email, 'password' => 'password', 'role' => 'brand']);
    $this->assertAuthenticated('brand');

    // Then sign in as a talent in the same browser session.
    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent']);

    // The talent is the single active identity; the stale brand session is gone.
    $this->assertAuthenticated('talent');
    $this->assertGuest('brand');

    // The shared dispatcher lands on the talent dashboard, not the brand one.
    $this->get('/dashboard')->assertRedirect(route('talent.dashboard'));
});

it('ignores a cross-guard intended URL so a talent login is not bounced to a brand route', function () {
    $talent = Talent::factory()->create();

    // A guest touching a brand route captures /brand/discover as the intended URL.
    $this->get('/brand/discover')->assertRedirect(route('login'));

    // Logging in as a talent must land on the talent dashboard, not the brand page.
    $this->post('/login', ['email' => $talent->email, 'password' => 'password', 'role' => 'talent'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticated('talent');
    $this->get('/dashboard')->assertRedirect(route('talent.dashboard'));
});

it('rejects an unknown role', function () {
    $user = User::factory()->create();

    $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'role' => 'superuser',
    ])->assertSessionHasErrors('role');

    $this->assertGuest();
});
