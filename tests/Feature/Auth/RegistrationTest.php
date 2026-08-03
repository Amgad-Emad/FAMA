<?php

use App\Models\Brand;
use App\Models\Talent;
use App\Models\User;

test('registration screen can be rendered', function () {
    $this->get('/register')->assertStatus(200);
});

test('a talent can self-register and lands on the talent dashboard', function () {
    $response = $this->post('/register', [
        'account_type' => 'talent',
        'name' => 'New Talent',
        'email' => 'new-talent@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated('talent');
    $response->assertRedirect(route('dashboard', absolute: false));

    $talent = Talent::where('email', 'new-talent@example.com')->firstOrFail();
    expect($talent->display_name)->toBe('New Talent');
    expect($talent->slug)->not->toBeEmpty();
    // Self-serve accounts start unpublished / draft (moderation funnel).
    expect((bool) $talent->is_published)->toBeFalse();
    expect($talent->status->getValue())->toBe('draft');
});

test('a brand can self-register and lands on the brand dashboard', function () {
    $this->post('/register', [
        'account_type' => 'brand',
        'name' => 'New Brand Co',
        'email' => 'new-brand@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated('brand');

    $brand = Brand::where('email', 'new-brand@example.com')->firstOrFail();
    expect($brand->name)->toBe('New Brand Co');
    expect((bool) $brand->is_published)->toBeFalse();
    expect($brand->status->getValue())->toBe('registered');
});

test('registration requires a valid account type and does not create a User', function () {
    // Missing type.
    $this->from('/register')->post('/register', [
        'name' => 'X', 'email' => 'x@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasErrors('account_type');

    // Admin self-signup is not offered (ADR-I).
    $this->from('/register')->post('/register', [
        'account_type' => 'admin', 'name' => 'X', 'email' => 'x2@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasErrors('account_type');

    expect(User::count())->toBe(0);
    $this->assertGuest();
});

test('registration email is unique within the chosen entity', function () {
    Talent::factory()->create(['email' => 'taken@example.com']);

    $this->from('/register')->post('/register', [
        'account_type' => 'talent',
        'name' => 'Dup', 'email' => 'taken@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');
});
