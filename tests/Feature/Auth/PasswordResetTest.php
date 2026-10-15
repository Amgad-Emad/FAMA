<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});

test('a talent can request a reset link and reset their password', function () {
    Notification::fake();

    $talent = \App\Models\Talent::factory()->create();

    $this->post('/forgot-password', ['email' => $talent->email])->assertSessionHasNoErrors();

    Notification::assertSentTo($talent, ResetPassword::class, function ($notification) use ($talent) {
        // The link names the account type so the reset hits the talents broker.
        expect($notification->toMail($talent)->actionUrl)->toContain('account=talent');

        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $talent->email,
            'account' => 'talent',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        expect(\Illuminate\Support\Facades\Hash::check('new-password-123', $talent->fresh()->password))->toBeTrue();

        return true;
    });

    $this->assertDatabaseCount('talent_password_reset_tokens', 0);
});

test('a talent reset works from an old link without the account hint', function () {
    Notification::fake();

    $talent = \App\Models\Talent::factory()->create();

    $this->post('/forgot-password', ['email' => $talent->email]);

    Notification::assertSentTo($talent, ResetPassword::class, function ($notification) use ($talent) {
        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $talent->email,
            'password' => 'another-pass-123',
            'password_confirmation' => 'another-pass-123',
        ])->assertSessionHasNoErrors();

        expect(\Illuminate\Support\Facades\Hash::check('another-pass-123', $talent->fresh()->password))->toBeTrue();

        return true;
    });
});

test('the same email as an admin and a talent keeps separate tokens and resets the right account', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'shared@fama.test']);
    $talent = \App\Models\Talent::factory()->create(['email' => 'shared@fama.test']);

    // Talent wins by default on the public form…
    $this->post('/forgot-password', ['email' => 'shared@fama.test']);
    Notification::assertSentTo($talent, ResetPassword::class);
    Notification::assertNotSentTo($user, ResetPassword::class);

    $this->assertDatabaseCount('talent_password_reset_tokens', 1);
    $this->assertDatabaseCount('password_reset_tokens', 0);

    $userPassword = $user->fresh()->password;

    Notification::assertSentTo($talent, ResetPassword::class, function ($notification) use ($talent, $user, $userPassword) {
        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => 'shared@fama.test',
            'account' => 'talent',
            'password' => 'talent-only-123',
            'password_confirmation' => 'talent-only-123',
        ])->assertSessionHasNoErrors();

        expect(\Illuminate\Support\Facades\Hash::check('talent-only-123', $talent->fresh()->password))->toBeTrue()
            ->and($user->fresh()->password)->toBe($userPassword);

        return true;
    });
});

test('an unknown email gets the friendly not-found error', function () {
    $this->post('/forgot-password', ['email' => 'nobody@fama.test'])->assertSessionHasErrors('email');
});
