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

it('rejects an unknown role', function () {
    $user = User::factory()->create();

    $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'role' => 'superuser',
    ])->assertSessionHasErrors('role');

    $this->assertGuest();
});
