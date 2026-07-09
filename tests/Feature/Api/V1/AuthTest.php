<?php

use App\Models\Brand;
use App\Models\Talent;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

// Every request goes through api() so Sanctum re-resolves the bearer token each
// time (see tests/Pest.php) — otherwise the guard caches the first user across a
// test's sub-requests and token rotation/scoping can't be asserted.

// ---------------------------------------------------------------------------
// Talent guard — public sign-up + the full token lifecycle.
// ---------------------------------------------------------------------------

it('registers a talent and issues a talent-scoped token', function () {
    $response = api()->postJson('/api/v1/talent/register', [
        'display_name' => 'Amgad Emad',
        'email' => 'new.talent@fama.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated();

    $response->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.abilities', ['talent'])
        ->assertJsonPath('data.talent.display_name', 'Amgad Emad');

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
    $this->assertDatabaseHas('talents', ['email' => 'new.talent@fama.test']);
});

it('logs a talent in, returns me, and rejects bad credentials', function () {
    $talent = Talent::factory()->create(['email' => 't@fama.test', 'password' => 'password123']);

    api()->postJson('/api/v1/talent/login', ['email' => 't@fama.test', 'password' => 'wrong'])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors('email');

    $token = api()->postJson('/api/v1/talent/login', ['email' => 't@fama.test', 'password' => 'password123'])
        ->assertOk()->json('data.token');

    api()->withToken($token)->getJson('/api/v1/talent/me')
        ->assertOk()
        ->assertJsonPath('data.id', $talent->id)
        ->assertJsonPath('data.slug', $talent->slug);
});

it('rotates the token on refresh and revokes it on logout', function () {
    $talent = Talent::factory()->create(['email' => 't@fama.test', 'password' => 'password123']);
    $token = api()->postJson('/api/v1/talent/login', ['email' => 't@fama.test', 'password' => 'password123'])
        ->assertOk()->json('data.token');

    // Refresh issues a new token and invalidates the presented one.
    $fresh = api()->withToken($token)->postJson('/api/v1/talent/refresh')->assertOk()->json('data.token');
    expect($fresh)->not->toBe($token);
    expect($talent->tokens()->count())->toBe(1); // old dropped, new issued
    api()->withToken($token)->getJson('/api/v1/talent/me')->assertUnauthorized();
    api()->withToken($fresh)->getJson('/api/v1/talent/me')->assertOk();

    // Logout revokes the current token.
    api()->withToken($fresh)->postJson('/api/v1/talent/logout')->assertOk();
    expect($talent->tokens()->count())->toBe(0);
    api()->withToken($fresh)->getJson('/api/v1/talent/me')->assertUnauthorized();
});

// ---------------------------------------------------------------------------
// Brand guard.
// ---------------------------------------------------------------------------

it('registers and authenticates a brand with a brand-scoped token', function () {
    $token = api()->postJson('/api/v1/brand/register', [
        'name' => 'Nomad Coffee',
        'email' => 'hi@nomad.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated()->assertJsonPath('data.abilities', ['brand'])->json('data.token');

    api()->withToken($token)->getJson('/api/v1/brand/me')
        ->assertOk()
        ->assertJsonPath('data.name', 'Nomad Coffee');

    $this->assertDatabaseHas('brands', ['email' => 'hi@nomad.test']);
});

// ---------------------------------------------------------------------------
// Admin guard — login works, but staff are provisioned (not self-registered).
// ---------------------------------------------------------------------------

it('logs an admin in and returns permission abilities', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create(['email' => 'boss@fama.test', 'password' => 'password123']);
    $admin->assignRole('super-admin');

    $response = api()->postJson('/api/v1/admin/login', ['email' => 'boss@fama.test', 'password' => 'password123'])
        ->assertOk();

    expect($response->json('data.abilities'))->toContain('admin', 'manage-users');

    api()->withToken($response->json('data.token'))->getJson('/api/v1/admin/me')
        ->assertOk()->assertJsonPath('data.email', 'boss@fama.test');
});

it('provisions a new admin only for a manage-users holder', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $superToken = tokenFor(tap(User::factory()->create(['password' => 'password123']), fn ($u) => $u->assignRole('super-admin')), 'admin');
    $supportToken = tokenFor(tap(User::factory()->create(['password' => 'password123']), fn ($u) => $u->assignRole('support')), 'admin');

    // support lacks manage-users → forbidden.
    api()->withToken($supportToken)->postJson('/api/v1/admin/register', [
        'name' => 'Mod', 'email' => 'mod@fama.test', 'password' => 'password123', 'password_confirmation' => 'password123',
    ])->assertForbidden();

    // super-admin may provision.
    api()->withToken($superToken)->postJson('/api/v1/admin/register', [
        'name' => 'Mod', 'email' => 'mod@fama.test', 'password' => 'password123', 'password_confirmation' => 'password123',
        'roles' => ['moderator'],
    ])->assertCreated()->assertJsonPath('data.roles', ['moderator']);

    expect(User::where('email', 'mod@fama.test')->first()->hasRole('moderator'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Cross-guard ability scoping + unauthenticated access.
// ---------------------------------------------------------------------------

it('scopes tokens to their guard', function () {
    $talentToken = tokenFor(Talent::factory()->create(), 'talent');
    $brandToken = tokenFor(Brand::factory()->create(), 'brand');

    // A talent token cannot reach a brand-only endpoint, and vice versa.
    api()->withToken($talentToken)->getJson('/api/v1/brand/me')->assertForbidden();
    api()->withToken($brandToken)->getJson('/api/v1/talent/me')->assertForbidden();
});

it('rejects anonymous access with a 401 envelope', function () {
    $this->getJson('/api/v1/talent/me')
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('data', null);
});

/**
 * Issue a plain-text Sanctum token for a model with the guard's ability set.
 */
function tokenFor($model, string $guard): string
{
    $abilities = $guard === 'admin'
        ? array_values(array_unique(['admin', ...$model->getAllPermissions()->pluck('name')->all()]))
        : [$guard];

    return $model->createToken('test', $abilities)->plainTextToken;
}
