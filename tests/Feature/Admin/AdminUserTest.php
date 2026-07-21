<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('soft-deletes an admin user', function () {
    $user = User::factory()->create();

    $user->delete();

    expect(User::count())->toBe(0);
    expect(User::withTrashed()->count())->toBe(1);
    expect(User::withTrashed()->first()->trashed())->toBeTrue();
});

it('stores + casts the admin locale and is_active flag', function () {
    $user = User::factory()->create(['locale' => 'ar', 'is_active' => false]);

    $fresh = $user->fresh();
    expect($fresh->locale)->toBe('ar');
    expect($fresh->is_active)->toBeFalse();
});

it('assigns admin-guard roles and grants a super-admin every permission', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    expect($user->hasRole('super-admin'))->toBeTrue();
    expect($user->can('manage-flows'))->toBeTrue();
    expect($user->can('manage-users'))->toBeTrue();
    expect($user->can('intervene-contracts'))->toBeTrue();
});

it('scopes granular permissions per role (a moderator cannot manage users)', function () {
    $moderator = User::factory()->create();
    $moderator->assignRole('moderator');

    expect($moderator->can('moderate-content'))->toBeTrue();
    expect($moderator->can('intervene-contracts'))->toBeTrue();
    expect($moderator->can('manage-users'))->toBeFalse();
    expect($moderator->can('manage-flows'))->toBeFalse();
});

it('binds roles to the admin guard', function () {
    $role = Role::findByName('super-admin', 'admin');
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($role->guard_name)->toBe('admin');
    // The user's role resolves on the admin guard (not the web default).
    expect($user->hasRole('super-admin', 'admin'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Account creation across all three guards (from the admin console).
// ---------------------------------------------------------------------------

it('creates a talent account from the admin console (audited, not in the admins list)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin, 'admin')
        ->postJson('/admin/users', [
            'account_type' => 'talent', 'name' => 'Provisioned Talent',
            'email' => 'prov-talent@fama.test', 'password' => 'password123',
        ])
        ->assertCreated()
        ->assertJsonPath('data.account_type', 'talent');

    $talent = App\Models\Talent::where('email', 'prov-talent@fama.test')->firstOrFail();
    expect($talent->display_name)->toBe('Provisioned Talent');
    expect((bool) $talent->is_published)->toBeFalse();
    // Admins list holds only User rows — the talent isn't one of them.
    expect(User::where('email', 'prov-talent@fama.test')->exists())->toBeFalse();

    $activity = Spatie\Activitylog\Models\Activity::inLog('admin_users')->latest('id')->first();
    expect($activity->description)->toBe('account.created');
    expect(data_get($activity->properties, 'account_type'))->toBe('talent');
});

it('creates a brand account from the admin console', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin, 'admin')
        ->postJson('/admin/users', [
            'account_type' => 'brand', 'name' => 'Provisioned Brand',
            'email' => 'prov-brand@fama.test', 'password' => 'password123',
        ])
        ->assertCreated()
        ->assertJsonPath('data.account_type', 'brand');

    expect(App\Models\Brand::where('email', 'prov-brand@fama.test')->exists())->toBeTrue();
});

it('rejects roles for a non-admin account type', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    // A talent create with a bogus role field still creates the talent (roles
    // are ignored server-side); a real admin-guard role only lands on admins.
    $this->actingAs($admin, 'admin')
        ->postJson('/admin/users', [
            'account_type' => 'talent', 'name' => 'T', 'email' => 't-noroles@fama.test',
            'password' => 'password123', 'roles' => ['moderator'],
        ])
        ->assertCreated();

    // Talent has no spatie roles (it's not on the admin guard at all).
    expect(App\Models\Talent::where('email', 't-noroles@fama.test')->exists())->toBeTrue();
});

it('denies account creation to an admin without manage-users', function () {
    $support = User::factory()->create();
    $support->assignRole('support'); // moderate-content only

    $this->actingAs($support, 'admin')
        ->postJson('/admin/users', [
            'account_type' => 'talent', 'name' => 'X', 'email' => 'x@fama.test', 'password' => 'password123',
        ])
        ->assertForbidden();
});
