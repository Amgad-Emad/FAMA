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
    expect($user->can('intervene-deals'))->toBeTrue();
});

it('scopes granular permissions per role (a moderator cannot manage users)', function () {
    $moderator = User::factory()->create();
    $moderator->assignRole('moderator');

    expect($moderator->can('moderate-content'))->toBeTrue();
    expect($moderator->can('intervene-deals'))->toBeTrue();
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
