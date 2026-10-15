<?php

use App\Models\Talent;
use App\Models\User;

it('shows the friendly copy when the email and password do not match', function () {
    $talent = Talent::factory()->create();

    $this->post('/login', ['email' => $talent->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['email' => "That email and password don't match. Try again, or reset your password."]);
});

it('uses the same friendly copy on the staff login', function () {
    $admin = User::factory()->create();

    $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['email' => "That email and password don't match. Try again, or reset your password."]);
});

it('asks for the email and password in plain words on login', function () {
    $this->post('/login', ['email' => '', 'password' => ''])
        ->assertSessionHasErrors([
            'email' => 'Enter the email you signed up with.',
            'password' => 'Enter your password.',
        ]);

    $this->post('/login', ['email' => 'not-an-email', 'password' => 'x'])
        ->assertSessionHasErrors(['email' => 'Enter an email address like name@studio.com.']);
});

it('points a taken email at logging in instead', function () {
    $talent = Talent::factory()->create(['email' => 'taken@fama.test']);

    $this->post('/register', [
        'account_type' => 'talent', 'name' => 'Someone', 'email' => $talent->email,
        'password' => 'password-123', 'password_confirmation' => 'password-123',
    ])->assertSessionHasErrors(['email' => 'This email already has an account. Log in instead.']);
});

it('uses friendly copy for every register field', function () {
    $this->post('/register', ['account_type' => 'talent', 'name' => '', 'email' => '', 'password' => '', 'password_confirmation' => ''])
        ->assertSessionHasErrors([
            'name' => 'Add your full name.',
            'email' => 'Enter an email address like name@studio.com.',
            'password' => 'Use at least 8 characters, and mix in letters, numbers or symbols.',
        ]);

    $this->post('/register', ['account_type' => 'talent', 'name' => 'A', 'email' => 'a@fama.test', 'password' => 'short', 'password_confirmation' => 'short'])
        ->assertSessionHasErrors(['password' => 'Use at least 8 characters, and mix in letters, numbers or symbols.']);

    $this->post('/register', ['account_type' => 'talent', 'name' => 'A', 'email' => 'a@fama.test', 'password' => 'password-123', 'password_confirmation' => 'password-456'])
        ->assertSessionHasErrors(['password' => "The passwords don't match."]);
});

it('shows the friendly copy on the page itself', function () {
    Talent::factory()->create(['email' => 'taken@fama.test']);

    $this->from('/register')->followingRedirects()->post('/register', [
        'account_type' => 'talent', 'name' => 'Someone', 'email' => 'taken@fama.test',
        'password' => 'password-123', 'password_confirmation' => 'password-123',
    ])->assertSee('This email already has an account. Log in instead.', false);
});
