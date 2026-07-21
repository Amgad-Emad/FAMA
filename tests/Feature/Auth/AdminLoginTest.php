<?php

use App\Models\User;

// The staff console has its OWN login: routes (GET|POST /admin/login), view,
// and request pinned to the admin guard — fully separate from the public
// role-aware /login (which no longer offers or accepts the admin role).

it('renders the dedicated staff login on its own view (no role control)', function () {
    $html = $this->get('/admin/login')->assertOk()->getContent();

    expect($html)->toContain(__('Staff sign in'));
    expect($html)->toContain(__('Restricted area'));
    expect($html)->toContain(route('admin.login.store'));
    // No role field — the guard is pinned server-side.
    expect($html)->not->toContain('name="role"');
    // Distinct layout, not the public guest card.
    expect($html)->toContain(__('Operations console'));
    expect($html)->toContain('noindex');
});

it('sends an unauthenticated admin-area guest to the staff login, not the public one', function () {
    $this->get('/admin/flows')->assertRedirect(route('admin.login'));
    $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
});

it('redirects an already-authenticated admin away from the staff login', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'admin')->get('/admin/login')->assertRedirect();
});

it('rate limits and reports invalid staff credentials on the email field', function () {
    $user = User::factory()->create();

    $this->from('/admin/login')->post('/admin/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertRedirect('/admin/login')->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

it('keeps the show-password toggle on the staff login', function () {
    // (/ar RTL is verified live — mcamara's locale prefix is per-request and
    // not resolvable inside feature tests, same as every other /ar URL.)
    $html = $this->get('/admin/login')->assertOk()->getContent();
    expect($html)->toContain(__('Show password'));
});
