<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $this->get('/login')->assertStatus(200);
    $this->get('/admin/login')->assertStatus(200);
});

test('users can authenticate using the staff login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('admin.dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('a talent can log out — the route is not gated on the default (admin) guard', function () {
    $talent = \App\Models\Talent::factory()->create();

    $response = $this->actingAs($talent, 'talent')->post('/logout');

    // Before the fix the plain `auth` middleware bounced this to /login without
    // running the controller, so the talent stayed signed in.
    $response->assertRedirect('/');
    $this->assertGuest('talent');
});
